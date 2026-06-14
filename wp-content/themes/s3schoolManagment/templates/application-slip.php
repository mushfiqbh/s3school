
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Application Slip - <?php echo esc_html($application->stdName); ?></title>
	<link href="https://fonts.googleapis.com/css?family=Quicksand:400,600,700" rel="stylesheet">
	<style>
		/* Layout tightening for A4 fit */
		body { font-family: 'Quicksand', Arial, sans-serif; background: #f5f5f5; padding: 16px; }
		.print-btn { background: #2563eb; color: #fff; border: none; padding: 8px 22px; font-size: 15px; font-weight: 600; border-radius: 6px; cursor: pointer; margin-bottom: 12px; }
		.print-btn:hover { background: #1d4ed8; }
		.slip-main { max-width: 900px; margin: 0 auto; background: #fff; border: 2px solid #2563eb; box-shadow: 0 0 12px rgba(37,99,235,0.08); padding: 0; }
		.slip-header { text-align: center; border-bottom: 2px solid #2563eb; padding: 16px 0 10px 0; }
		.slip-logo { max-width: 80px; max-height: 80px; margin-bottom: 6px; }
		.slip-title { font-size: 21px; font-weight: 700; color: #2563eb; margin-top: 8px; letter-spacing: 0.8px; }
		.slip-inst-name { font-size: 24px; font-weight: 700; color: #1e293b; margin-bottom: 4px; text-transform: uppercase; }
		.slip-inst-address { font-size: 13px; color: #64748b; margin-bottom: 4px; }
		.slip-ref { font-size: 14px; color: #78350f; background: #fef3c7; border-left: 4px solid #f59e0b; padding: 6px 14px; margin: 12px 0; border-radius: 4px; display: inline-block; }
		.slip-part-label { background: #2563eb; color: #fff; padding: 5px 14px; font-weight: 600; font-size: 13px; border-radius: 4px; margin-bottom: 12px; display: inline-block; letter-spacing: 0.8px; }
		.slip-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
		.slip-table th, .slip-table td { border: 1px solid #c5d5e4; padding: 6px 10px; font-size: 14px; }
		.slip-table th { background: #f1f5f9; font-weight: 700; color: #2563eb; }
		.slip-table td { color: #1e293b; }
		.slip-photo { float: right; width: 105px; height: 125px; border: 2px solid #2563eb; border-radius: 8px; background: #f1f5f9; margin-left: 14px; margin-bottom: 8px; overflow: hidden; }
		.slip-photo img { width: 100%; height: 100%; object-fit: cover; }
		.signature-area { display: flex; justify-content: space-between; margin-top: 10px; }
		.signature-box { text-align: center; flex: 0 0 180px; }
		.signature-label { font-size: 12px; font-weight: 600; color: #475569; }
		.page-break { page-break-after: always; }

		/* Print specific: fit to A4, remove outer padding, hide non-print elements */
		@page { size: A4; margin: 10mm; }
		@media print {
			body { background: #fff; padding: 0; }
			.no-print, .print-btn { display: none !important; }
			.slip-main { box-shadow: none !important; border: 2px solid #2563eb; }
			/* Avoid element breaks */
			.slip-table, .signature-area { break-inside: avoid; }
		}
	</style>
</head>
<body>
	<div style="text-align:center" class="no-print">
		<button class="print-btn" onclick="window.print()">🖨️ Print Application Slip</button>
	</div>
	<div class="slip-main">
		<!-- Applicant's Copy -->
		<div style="padding:20px;">
			<div class="slip-header">
				<?php if (!empty($s3sRedux['instLogo'])) : ?>
					<img src="<?php echo esc_url($s3sRedux['instLogo']); ?>" alt="Logo" class="slip-logo">
				<?php endif; ?>
				<div class="slip-inst-name"><?php echo esc_html($s3sRedux['instName'] ?? 'School Name'); ?></div>
				<div class="slip-inst-address"><?php echo esc_html($s3sRedux['instAddress'] ?? ''); ?></div>
				<?php if (!empty($s3sRedux['instPhone'])) : ?>
					<div class="slip-inst-address">Phone: <?php echo esc_html($s3sRedux['instPhone']); ?></div>
				<?php endif; ?>
				<div class="slip-title">Admission Application Slip</div>
			</div>
			<div class="slip-part-label">Applicant's Copy</div>
			<div class="slip-ref">Application Reference No: <strong>APP-<?php echo str_pad($application->id, 6, '0', STR_PAD_LEFT); ?></strong></div>
			<?php if (!empty($application->stdImg)) : ?>
				<div class="slip-photo"><img src="<?php echo esc_url($application->stdImg); ?>" alt="Student Photo"></div>
			<?php endif; ?>
			<table class="slip-table">
				<tr><th colspan="2">Personal Information</th></tr>
				<tr><td>Student Name (English)</td><td><?php echo esc_html($application->stdName); ?></td></tr>
				<?php if (!empty($application->stdNameBangla)) : ?><tr><td>Student Name (Bangla)</td><td><?php echo esc_html($application->stdNameBangla); ?></td></tr><?php endif; ?>
				<tr><td>Gender</td><td><?php $genderLabels = ['0' => 'Girl', '1' => 'Boy', '2' => 'Other']; echo esc_html($genderLabels[$application->stdGender] ?? 'Not specified'); ?></td></tr>
				<tr><td>Date of Birth</td><td><?php echo esc_html($application->stdBrith ? date('d F, Y', strtotime($application->stdBrith)) : 'N/A'); ?></td></tr>
				<tr><td>Religion</td><td><?php echo esc_html($application->stdReligion); ?></td></tr>
				<?php if (!empty($application->facilities)) : ?><tr><td>Facilities</td><td><?php echo esc_html($application->facilities); ?></td></tr><?php endif; ?>
			</table>
			<table class="slip-table">
				<tr><th colspan="2">Academic Information</th></tr>
				<tr><td>Applying for Class</td><td><?php echo esc_html($application->className ?? 'N/A'); ?></td></tr>
				<tr><td>Admission Year</td><td><?php echo esc_html($application->stdAdmitYear); ?></td></tr>
				<?php if (!empty($application->stdPrevSchool)) : ?><tr><td>Previous School</td><td><?php echo esc_html($application->stdPrevSchool); ?></td></tr><?php endif; ?>
				<?php if (!empty($application->stdGPA)) : ?><tr><td>Previous GPA</td><td><?php echo esc_html($application->stdGPA); ?></td></tr><?php endif; ?>
				<?php if (!empty($application->stdTcNumber)) : ?><tr><td>TC Number</td><td><?php echo esc_html($application->stdTcNumber); ?></td></tr><?php endif; ?>
			</table>
			<table class="slip-table">
				<tr><th colspan="2">Guardian Information</th></tr>
				<tr><td>Father's Name</td><td><?php echo esc_html($application->stdFather); ?></td></tr>
				<tr><td>Mother's Name</td><td><?php echo esc_html($application->stdMother); ?></td></tr>
				<?php if (!empty($application->stdlocalGuardian)) : ?><tr><td>Local Guardian</td><td><?php echo esc_html($application->stdlocalGuardian); ?></td></tr><?php endif; ?>
				<?php if (!empty($application->stdGuardianNID)) : ?><tr><td>Guardian NID</td><td><?php echo esc_html($application->stdGuardianNID); ?></td></tr><?php endif; ?>
				<tr><td>Contact Phone</td><td><?php echo esc_html($application->stdPhone); ?></td></tr>
			</table>
			<table class="slip-table">
				<tr><th colspan="2">Address</th></tr>
				<tr><td>Present Address</td><td><?php echo esc_html($application->stdPresent); ?></td></tr>
				<tr><td>Permanent Address</td><td><?php echo esc_html($application->stdPermanent); ?></td></tr>
			</table>
			<?php if (!empty($application->stdNote)) : ?>
				<table class="slip-table"><tr><th>Additional Notes</th></tr><tr><td><?php echo nl2br(esc_html($application->stdNote)); ?></td></tr></table>
			<?php endif; ?>
			<?php if (!empty($application->paymentPaid) || !empty($application->paymentDue)) : ?>
				<table class="slip-table"><tr><th colspan="2">Payment Information</th></tr>
					<?php if (!empty($application->paymentPaid)) : ?><tr><td>Payment Paid</td><td><?php echo esc_html($application->paymentPaid); ?></td></tr><?php endif; ?>
					<?php if (!empty($application->paymentDue)) : ?><tr><td>Payment Due</td><td><?php echo esc_html($application->paymentDue); ?></td></tr><?php endif; ?>
				</table>
			<?php endif; ?>
			<div class="signature-area">
				<div class="signature-box"><div class="signature-label">Applicant/Guardian Signature</div></div>
				<div class="signature-box"><div class="signature-label">Date: <?php echo date('d/m/Y'); ?></div></div>
			</div>
		</div>
		<div class="page-break" style="margin:0;"></div>
		<!-- Institute's Copy -->
		<div style="padding:20px;">
			<div class="slip-header">
				<?php if (!empty($s3sRedux['instLogo'])) : ?>
					<img src="<?php echo esc_url($s3sRedux['instLogo']); ?>" alt="Logo" class="slip-logo">
				<?php endif; ?>
				<div class="slip-inst-name"><?php echo esc_html($s3sRedux['instName'] ?? 'School Name'); ?></div>
				<div class="slip-inst-address"><?php echo esc_html($s3sRedux['instAddress'] ?? ''); ?></div>
				<?php if (!empty($s3sRedux['instPhone'])) : ?>
					<div class="slip-inst-address">Phone: <?php echo esc_html($s3sRedux['instPhone']); ?></div>
				<?php endif; ?>
				<div class="slip-title">Admission Application Slip</div>
			</div>
			<div class="slip-part-label">Institute's Copy</div>
			<div class="slip-ref">Application Reference No: <strong>APP-<?php echo str_pad($application->id, 6, '0', STR_PAD_LEFT); ?></strong></div>
			<?php if (!empty($application->stdImg)) : ?>
				<div class="slip-photo"><img src="<?php echo esc_url($application->stdImg); ?>" alt="Student Photo"></div>
			<?php endif; ?>
			<table class="slip-table">
				<tr><th colspan="2">Personal Information</th></tr>
				<tr><td>Student Name (English)</td><td><?php echo esc_html($application->stdName); ?></td></tr>
				<?php if (!empty($application->stdNameBangla)) : ?><tr><td>Student Name (Bangla)</td><td><?php echo esc_html($application->stdNameBangla); ?></td></tr><?php endif; ?>
				<tr><td>Gender</td><td><?php $genderLabels = ['0' => 'Girl', '1' => 'Boy', '2' => 'Other']; echo esc_html($genderLabels[$application->stdGender] ?? 'Not specified'); ?></td></tr>
				<tr><td>Date of Birth</td><td><?php echo esc_html($application->stdBrith ? date('d F, Y', strtotime($application->stdBrith)) : 'N/A'); ?></td></tr>
				<tr><td>Religion</td><td><?php echo esc_html($application->stdReligion); ?></td></tr>
				<?php if (!empty($application->facilities)) : ?><tr><td>Facilities</td><td><?php echo esc_html($application->facilities); ?></td></tr><?php endif; ?>
			</table>
			<table class="slip-table">
				<tr><th colspan="2">Academic Information</th></tr>
				<tr><td>Applying for Class</td><td><?php echo esc_html($application->className ?? 'N/A'); ?></td></tr>
				<tr><td>Admission Year</td><td><?php echo esc_html($application->stdAdmitYear); ?></td></tr>
				<?php if (!empty($application->stdPrevSchool)) : ?><tr><td>Previous School</td><td><?php echo esc_html($application->stdPrevSchool); ?></td></tr><?php endif; ?>
				<?php if (!empty($application->stdGPA)) : ?><tr><td>Previous GPA</td><td><?php echo esc_html($application->stdGPA); ?></td></tr><?php endif; ?>
				<?php if (!empty($application->stdTcNumber)) : ?><tr><td>TC Number</td><td><?php echo esc_html($application->stdTcNumber); ?></td></tr><?php endif; ?>
			</table>
			<table class="slip-table">
				<tr><th colspan="2">Guardian Information</th></tr>
				<tr><td>Father's Name</td><td><?php echo esc_html($application->stdFather); ?></td></tr>
				<tr><td>Mother's Name</td><td><?php echo esc_html($application->stdMother); ?></td></tr>
				<?php if (!empty($application->stdlocalGuardian)) : ?><tr><td>Local Guardian</td><td><?php echo esc_html($application->stdlocalGuardian); ?></td></tr><?php endif; ?>
				<?php if (!empty($application->stdGuardianNID)) : ?><tr><td>Guardian NID</td><td><?php echo esc_html($application->stdGuardianNID); ?></td></tr><?php endif; ?>
				<tr><td>Contact Phone</td><td><?php echo esc_html($application->stdPhone); ?></td></tr>
			</table>
			<table class="slip-table">
				<tr><th colspan="2">Address</th></tr>
				<tr><td>Present Address</td><td><?php echo esc_html($application->stdPresent); ?></td></tr>
				<tr><td>Permanent Address</td><td><?php echo esc_html($application->stdPermanent); ?></td></tr>
			</table>
			<?php if (!empty($application->stdNote)) : ?>
				<table class="slip-table"><tr><th>Additional Notes</th></tr><tr><td><?php echo nl2br(esc_html($application->stdNote)); ?></td></tr></table>
			<?php endif; ?>
			<?php if (!empty($application->paymentPaid) || !empty($application->paymentDue)) : ?>
				<table class="slip-table"><tr><th colspan="2">Payment Information</th></tr>
					<?php if (!empty($application->paymentPaid)) : ?><tr><td>Payment Paid</td><td><?php echo esc_html($application->paymentPaid); ?></td></tr><?php endif; ?>
					<?php if (!empty($application->paymentDue)) : ?><tr><td>Payment Due</td><td><?php echo esc_html($application->paymentDue); ?></td></tr><?php endif; ?>
				</table>
			<?php endif; ?>
			<div class="signature-area">
				<div class="signature-box"><div class="signature-label">Received By</div></div>
				<div class="signature-box"><div class="signature-label">Authorized Signature</div></div>
			</div>
		</div>
	</div>
</body>
</html>
