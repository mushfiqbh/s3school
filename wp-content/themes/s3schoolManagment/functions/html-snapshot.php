<?php
if (! function_exists('s3s_store_html_snapshot')) {
	/**
	 * Persist a chunk of HTML to uploads for reuse in print/PDF workflows.
	 */
	function s3s_store_html_snapshot(string $html, array $meta = [], array $args = []): array
	{
		$result = [
			'path' => '',
			'url' => '',
			'error' => ''
		];

		$html = trim($html);
		if ($html === '') {
			$result['error'] = 'Snapshot HTML is empty.';
			return $result;
		}

		$defaults = [
			'subdir' => 'snapshots',
			'prefix' => 'snapshot',
			'extension' => 'html',
			'timestamp' => (int) current_time('timestamp'),
			'uniqid' => uniqid('', false)
		];
		$args = wp_parse_args($args, $defaults);

		$uploadDir = wp_upload_dir();
		if (! empty($uploadDir['error'])) {
			$result['error'] = $uploadDir['error'];
			return $result;
		}

		$relativeSubdir = ltrim((string) $args['subdir'], '/');
		$targetDir = trailingslashit($uploadDir['basedir']);
		if ($relativeSubdir !== '') {
			$targetDir .= trailingslashit($relativeSubdir);
		}

		if (! wp_mkdir_p($targetDir)) {
			$result['error'] = 'Unable to create snapshot directory.';
			return $result;
		}

		$safePieces = [];
		foreach ($meta as $piece) {
			if ($piece === '' || $piece === null) {
				continue;
			}
			$safePieces[] = sanitize_title((string) $piece);
		}
		$safePieces = array_filter($safePieces);

		$stemParts = array_filter([
			sanitize_title((string) $args['prefix']),
			implode('-', $safePieces),
			gmdate('Ymd-His', (int) $args['timestamp']),
			substr((string) $args['uniqid'], 0, 8)
		]);

		$fileStem = implode('-', $stemParts);
		if ($fileStem === '') {
			$fileStem = 'snapshot';
		}

		$extension = preg_replace('/[^a-z0-9]+/i', '', (string) $args['extension']);
		if ($extension === '') {
			$extension = 'html';
		}

		$fileName = strtolower($fileStem) . '.' . strtolower($extension);
		$targetPath = $targetDir . $fileName;

		$writeResult = @file_put_contents($targetPath, $html, LOCK_EX);
		if ($writeResult === false) {
			$result['error'] = 'Unable to write snapshot HTML file.';
			return $result;
		}

		$baseUrl = trailingslashit($uploadDir['baseurl']);
		if ($relativeSubdir !== '') {
			$baseUrl .= trailingslashit($relativeSubdir);
		}

		$result['path'] = $targetPath;
		$result['url'] = $baseUrl . $fileName;

		return $result;
	}
}
