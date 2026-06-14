<?php
/**
* Template Name: Admin Attendance
*/
global $wpdb, $s3sRedux;

// Helpers
if (!function_exists('sm_clean_int')) {
    function sm_clean_int($v) { return isset($v) && $v !== '' ? (int)$v : null; }
}
if (!function_exists('sm_clean_txt')) {
    function sm_clean_txt($v) { return isset($v) ? sanitize_text_field($v) : ''; }
}

// ============================================
// NEW AJAX HANDLERS (Modern System)
// ============================================

$att_table = 'ct_attendance';

// 1. Fetch Students & Attendance
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fetch_attendance'])) {
    
    // Clean buffer
    while (ob_get_level()) { ob_end_clean(); }
    header('Content-Type: application/json');

    $classId = sm_clean_int($_POST['class_id']);
    $sectionId = sm_clean_int($_POST['section_id']);
    $year = sm_clean_txt($_POST['year']);
    $date = sm_clean_txt($_POST['date']);

    if (!$classId || !$year || !$date) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    // Get Students
    $studentsQuery = $wpdb->prepare(
        "SELECT 
            s.studentid, 
            s.stdName, 
            s.stdPhone, 
            si.infoRoll,
            si.infoStdid
        FROM ct_studentinfo si
        JOIN ct_student s ON si.infoStdid = s.studentid
        WHERE si.infoClass = %d 
        AND si.infoYear = %s",
        $classId,
        $year
    );

    if ($sectionId > 0) {
        $studentsQuery .= $wpdb->prepare(" AND si.infoSection = %d", $sectionId);
    }

    $studentsQuery .= " ORDER BY si.infoRoll ASC";
    $students = $wpdb->get_results($studentsQuery);

    // Get Existing Attendance for this Date
    $attQuery = $wpdb->prepare(
        "SELECT stdId, status, notes 
            FROM {$att_table} 
            WHERE attClass = %d 
            AND attYear = %s 
            AND attDate = %s",
            $classId, $year, $date
    );
    
    if ($sectionId > 0) {
        $attQuery .= $wpdb->prepare(" AND attSection = %d", $sectionId);
    }

    $attendanceData = $wpdb->get_results($attQuery, OBJECT_K);
    
    // Map attendance to students
    $attMap = [];
    foreach ($attendanceData as $row) {
        $attMap[$row->stdId] = $row;
    }

    $result = [];
    foreach ($students as $student) {
        $stdId = $student->studentid;
        $status = isset($attMap[$stdId]) ? $attMap[$stdId]->status : 'present';
        $notes = isset($attMap[$stdId]) ? $attMap[$stdId]->notes : '';
        
        $result[] = [
            'id' => $stdId,
            'roll' => $student->infoRoll,
            'name' => $student->stdName,
            'status' => $status,
            'notes' => $notes
        ];
    }

    echo json_encode(['success' => true, 'data' => $result]);
    exit;
}

// 2. Mark Single Attendance
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_single'])) {
    
    while (ob_get_level()) { ob_end_clean(); }
    header('Content-Type: application/json');

    $data = [
        'attDate' => sm_clean_txt($_POST['date']),
        'attClass' => sm_clean_int($_POST['class_id']),
        'attSection' => sm_clean_int($_POST['section_id']),
        'attYear' => sm_clean_txt($_POST['year']),
        'stdId' => sm_clean_int($_POST['student_id']),
        'infoRoll' => sm_clean_int($_POST['roll']),
        'status' => sm_clean_txt($_POST['status']),
        'notes' => isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '' 
    ];

    // Check if exists
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT attId FROM {$att_table} 
            WHERE attDate = %s AND stdId = %d AND attClass = %d",
        $data['attDate'], $data['stdId'], $data['attClass']
    ));

    if ($exists) {
        $wpdb->update(
            $att_table,
            ['status' => $data['status'], 'notes' => $data['notes']],
            ['attId' => $exists]
        );
    } else {
        $wpdb->insert($att_table, $data);
    }

    echo json_encode(['success' => true]);
    exit;
}

// 3. Bulk Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_bulk'])) {
    
    while (ob_get_level()) { ob_end_clean(); }
    header('Content-Type: application/json');

    $status = sm_clean_txt($_POST['status']);
    $classId = sm_clean_int($_POST['class_id']);
    $sectionId = sm_clean_int($_POST['section_id']);
    $year = sm_clean_txt($_POST['year']);
    $date = sm_clean_txt($_POST['date']);
    
    // Get all students for this class criteria
    $studentsQuery = $wpdb->prepare(
        "SELECT s.studentid, si.infoRoll
        FROM ct_studentinfo si
        JOIN ct_student s ON si.infoStdid = s.studentid
        WHERE si.infoClass = %d AND si.infoYear = %s",
        $classId, $year
    );
    if ($sectionId > 0) $studentsQuery .= $wpdb->prepare(" AND si.infoSection = %d", $sectionId);
    
    $students = $wpdb->get_results($studentsQuery);
    $count = 0;

    foreach ($students as $student) {
        $sql = "INSERT INTO {$att_table} 
                (attDate, attClass, attSection, attYear, stdId, infoRoll, status) 
                VALUES (%s, %d, %d, %s, %d, %d, %s)
                ON DUPLICATE KEY UPDATE status = %s";
        
        $wpdb->query($wpdb->prepare(
            $sql, 
            $date, $classId, $sectionId, $year, $student->studentid, $student->infoRoll, $status,
            $status
        ));
        $count++;
    }

    echo json_encode(['success' => true, 'updated' => $count]);
    exit;
}

// 4. Fetch Sections
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['get_sections'])) {

    while (ob_get_level()) { ob_end_clean(); }
    // Note: get_sections returns HTML options, not JSON
    // header('Content-Type: text/html'); 

    $class = sm_clean_int($_POST['class_id']);
    $sections = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT sectionid, sectionName FROM ct_section WHERE forClass = %d ORDER BY sectionName ASC",
            $class
        )
    );

    if (!empty($sections)) {
        echo "<option value='0'>All Sections</option>";
        foreach ($sections as $section) {
            echo "<option value='{$section->sectionid}'>{$section->sectionName}</option>";
        }
    } else {
        echo "<option value='0'>No Sections Found</option>";
    }
    exit;
}

if ( ! is_admin() ) { get_header(); }
?>

<!-- Monthly View Link Logic (Kept Simple) -->
<?php if(isset($_GET['view']) && $_GET['view'] == 'month'): ?>
    <?php 
    // Minimal fallback for monthly view or redirect to new report system
    // For now, let's just show a simple link back to daily because the old monthly code won't work with new table
    ?>
    <div class="container" style="padding: 20px;">
        <div class="alert alert-info">
            Monthly View is currently being upgraded. <a href="?page=attendance" class="btn btn-primary">Go Back to Daily Attendance</a>
        </div>
    </div>
<?php else: ?>

<style>
/* Modern UI Styles */
:root {
    --primary: #4a90e2;
    --success: #2ecc71;
    --danger: #e74c3c;
    --leave: #f39c12;
    --late: #2d9cdb;
    --gray: #f5f6fa;
    --text: #2c3e50;
    --border: #dcdde1;
}

.att-dashboard {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    margin-top: 20px;
    font-family: 'Segoe UI', sans-serif;
    color: var(--text);
}

.att-filters {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    background: var(--gray);
    padding: 15px;
    border-radius: 6px;
    align-items: flex-end;
}

.att-form-group {
    display: flex;
    flex-direction: column;
}

.att-form-group label {
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 5px;
    color: #7f8c8d;
    text-transform: uppercase;
}

.att-select, .att-date {
    padding: 8px 12px;
    border: 1px solid var(--border);
    border-radius: 4px;
    min-width: 150px;
}

.att-btn {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.2s;
}

.btn-blue { background: var(--primary); color: white; }
.btn-green { background: var(--success); color: white; }
.btn-red { background: var(--danger); color: white; }
.btn-outline { background: transparent; border: 1px solid var(--border); color: var(--text); }

.att-toolbar {
    margin: 20px 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid var(--gray);
    padding-bottom: 15px;
}

.sync-indicator {
    font-size: 12px;
    font-weight: 600;
    padding: 6px 10px;
    border-radius: 999px;
    border: 1px solid var(--border);
    background: #fff;
    color: #7f8c8d;
    margin-left: 12px;
    white-space: nowrap;
}

.sync-indicator.sync-saved { color: #1f8f4a; border-color: rgba(46, 204, 113, 0.35); background: rgba(46, 204, 113, 0.08); }
.sync-indicator.sync-saving { color: #1f5ea8; border-color: rgba(74, 144, 226, 0.35); background: rgba(74, 144, 226, 0.08); }
.sync-indicator.sync-error { color: #b0342a; border-color: rgba(231, 76, 60, 0.35); background: rgba(231, 76, 60, 0.08); }

.att-stats span {
    margin-left: 0px;
    font-weight: bold;
}

/* Student List */
.student-list {
	max-width: 500px;
	margin: 0 auto;
    display: grid;
    grid-template-columns: 1fr;
    gap: 15px;
    min-height: 200px;
}

.student-card {
    border: 1px solid var(--border);
    border-radius: 6px;
    padding: 12px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: white;
    transition: transform 0.1s;
}

.student-card:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.student-card.status-present { border-left: 5px solid var(--success); }
.student-card.status-absent { border-left: 5px solid var(--danger); }
.student-card.status-leave { border-left: 5px solid var(--leave); }
.student-card.status-late { border-left: 5px solid var(--late); }
.student-card.status-unmarked { border-left: 5px solid #bdc3c7; }

.std-info {
    flex-grow: 1;
}

.std-roll {
    font-size: 18px;
    font-weight: 900;
    background: #eef2f7;
    padding: 6px 10px;
    border-radius: 4px;
    color: #2c3e50;
    display: inline-block;
}

.std-name {
    font-weight: 600;
    font-size: 15px;
    display: block;
    margin-top: 4px;
}

.std-actions {
    display: flex;
    gap: 5px;
}

.action-btn {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    border: 1px solid var(--border);
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 16px;
    color: #bdc3c7;
    transition: all 0.2s;
}

.action-btn:hover { transform: scale(1.1); }

.btn-present.active, .btn-present:hover { 
    background: var(--success); 
    border-color: var(--success); 
    color: white; 
}

.btn-absent.active, .btn-absent:hover { 
    background: var(--danger); 
    border-color: var(--danger); 
    color: white; 
}

.btn-note { 
    color: #f39c12; 
    border-color: #f39c12;
}
.btn-note.has-note {
    background: #f39c12;
    color: white;
}

.btn-leave {
    color: var(--leave);
    border-color: var(--leave);
}
.btn-leave.active {
    background: var(--leave);
    color: white;
}

.btn-late {
    color: var(--late);
    border-color: var(--late);
}
.btn-late.active {
    background: var(--late);
    color: #fff;
}

/* In the Quick Mark modal, make Leave a filled high-contrast button */
.quick-secondary .btn-leave {
    background: var(--leave);
    color: #fff;
    border-color: var(--leave);
}

/* In the Quick Mark modal, make Late a filled high-contrast button */
.quick-secondary .btn-late {
    background: var(--late);
    color: #fff;
    border-color: var(--late);
}

/* Modal */
.modal-overlay {
    position: fixed; top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.5);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}
.modal-overlay.open { display: flex; }
.modal-box {
    background: white;
    padding: 20px;
    border-radius: 8px;
    width: 90%;
    max-width: 400px;
}
.modal-header { font-weight: bold; margin-bottom: 10px; font-size: 18px; }
.modal-textarea { width: 100%; height: 100px; padding: 10px; margin-bottom: 10px; border: 1px solid var(--border); }
.modal-footer { display: flex; justify-content: flex-end; gap: 10px; }

/* Toast */
.toast-msg {
    position: fixed;
    bottom: 20px;
    right: 20px;
    background: #333;
    color: white;
    padding: 12px 24px;
    border-radius: 4px;
    display: none;
    z-index: 2000;
}

/* Quick Mark Modal */
.quick-modal .modal-box {
    width: min(680px, calc(100vw - 24px));
    max-width: 680px;
}

.quick-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 10px;
}

.quick-meta {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.quick-pill {
    font-size: 12px;
    font-weight: 700;
    background: var(--gray);
    border: 1px solid var(--border);
    color: #566573;
    padding: 6px 10px;
    border-radius: 999px;
}

.quick-name {
    font-weight: 800;
    font-size: 18px;
    line-height: 1.2;
}

.quick-actions {
    margin: 10px 0 12px;
}

.quick-primary {
    display: flex;
    flex-direction: column;
    gap: 0;
}

.quick-primary .att-btn {
    width: 100%;
    min-height: 56px;
    padding: 16px 16px;
    font-size: 18px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.quick-secondary {
    display: flex;
    gap: 10px;
    margin-top: 12px;
}

.quick-secondary .att-btn {
    flex: 1;
    min-height: 48px;
    padding: 12px 12px;
    font-size: 15px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.quick-actions .att-btn.is-active {
    box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.18);
}

.quick-actions .att-btn:active {
    transform: scale(0.98);
}

.quick-actions .att-btn .q-ic {
    font-size: 18px;
    line-height: 1;
}

.quick-note-wrap {
    display: none;
    margin-top: 6px;
}

.quick-note-wrap.is-visible { display: block; }

.quick-chips {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-top: 8px;
}

.quick-chip {
    font-size: 12px;
    font-weight: 700;
    border: 1px solid var(--border);
    background: #fff;
    padding: 6px 10px;
    border-radius: 999px;
    cursor: pointer;
}

.quick-chip:active { transform: scale(0.98); }

.quick-hint {
    font-size: 12px;
    color: #7f8c8d;
    margin-top: 8px;
}

.quick-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    margin-top: 12px;
    position: sticky;
    bottom: 0;
    background: #fff;
    padding-top: 10px;
    border-top: 1px solid var(--gray);
}

.quick-footer .att-btn {
    min-width: 110px;
}

@media (max-width: 600px) {
    .quick-modal .modal-box {
        width: 100vw;
        height: 100vh;
        max-width: 100vw;
        max-height: 100vh;
        border-radius: 0;
        display: flex;
        flex-direction: column;
    }

    .quick-modal .modal-textarea {
        flex: 1;
        min-height: 160px;
    }

    .quick-footer {
        margin-top: auto;
        padding-top: 10px;
        border-top: 1px solid var(--gray);
    }
}

/* Undo snackbar */
.undo-bar {
    position: fixed;
    left: 50%;
    bottom: 18px;
    transform: translateX(-50%);
    background: rgba(17, 17, 17, 0.94);
    color: #fff;
    padding: 12px 14px;
    border-radius: 10px;
    display: none;
    align-items: center;
    gap: 12px;
    z-index: 2500;
    width: min(680px, calc(100vw - 24px));
}

.undo-bar .undo-msg { flex: 1; font-size: 14px; }
.undo-bar .undo-btn {
    background: transparent;
    color: #fff;
    border: 1px solid rgba(255,255,255,0.35);
    border-radius: 999px;
    padding: 8px 12px;
    font-weight: 800;
}
</style>

<div class="container-fluid b-layer-main">
    <div class="row">
        <div class="col-md-12">
            
            <div class="att-dashboard">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 15px;">
                    <h2 style="margin:0;">Daily Attendance</h2>
                    <a href="?page=attendance&view=month" class="btn btn-default btn-sm">Monthly Report (Maintenance)</a>
                </div>

                <!-- Filters -->
                <div class="att-filters">
                    <div class="att-form-group">
                        <label>Academic Year</label>
                        <select id="sYear" class="att-select">
                            <?php 
                            $years = $wpdb->get_results("SELECT infoYear FROM ct_studentinfo GROUP BY infoYear ORDER BY infoYear DESC");
                            foreach ($years as $y) echo "<option value='{$y->infoYear}'>{$y->infoYear}</option>";
                            ?>
                        </select>
                    </div>
                    
                    <div class="att-form-group">
                        <label>Class</label>
                        <select id="sClass" class="att-select">
                            <option value="">Select Class</option>
                            <?php 
                            // Only classes that have students
                            $classes = $wpdb->get_results("SELECT classid, className FROM ct_class ORDER BY className ASC");
                            foreach ($classes as $c) echo "<option value='{$c->classid}'>{$c->className}</option>";
                            ?>
                        </select>
                    </div>

                    <div class="att-form-group">
                        <label>Section</label>
                        <select id="sSection" class="att-select" disabled>
                            <option value="0">All Sections / None</option>
                        </select>
                    </div>

                    <div class="att-form-group">
                        <label>Date</label>
                        <input type="date" id="sDate" class="att-date" value="<?= date('Y-m-d') ?>">
                    </div>

                    <div class="att-form-group">
                        <label>&nbsp;</label>
                        <button type="button" id="btnLoad" class="att-btn btn-blue">Load Students</button>
                    </div>
                </div>

                <!-- Toolbar (Hidden until loaded) -->
                <div id="attToolbar" class="att-toolbar" style="display:none;">
                    <div class="bulk-actions">
                        <button type="button" class="att-btn btn-outline" onclick="app.openQuick()">Quick Mark</button>
                        <button class="att-btn btn-green" onclick="app.markBulk('present')">Mark All Present</button>
                        <button class="att-btn btn-red" onclick="app.markBulk('absent')">Mark All Absent</button>
                    </div>
                    <div class="att-stats">
                        Total: <span id="totalCount">0</span>&nbsp;&nbsp;
                        Present: <span id="presentCount" style="color:var(--success)">0</span>&nbsp;&nbsp;
                        Absent: <span id="absentCount" style="color:var(--danger)">0</span>&nbsp;&nbsp;
                        Leave: <span id="leaveCount" style="color:var(--leave)">0</span>&nbsp;&nbsp;
                        Late: <span id="lateCount" style="color:var(--late)">0</span>&nbsp;&nbsp;
                        <span id="syncIndicator" class="sync-indicator sync-saved" title="Autosave status">Saved</span>
                    </div>
                </div>

                <!-- Student List -->
                <div id="studentContainer" class="student-list">
                    <div style="grid-column: 1/-1; text-align:center; padding: 40px; color: #999;">
                        Select Class and click Load Students to begin.
                    </div>
                </div>

            </div>

        </div>
    </div>
</div>

<!-- Modal for Notes -->
<div id="noteModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">Add Note for <span id="modalStdName"></span></div>
        <textarea id="modalNoteText" class="modal-textarea" placeholder="Reason for absence, late arrival, etc..."></textarea>
        <input type="hidden" id="modalStdId">
        <div class="modal-footer">
            <button class="att-btn btn-outline" onclick="document.getElementById('noteModal').classList.remove('open')">Cancel</button>
            <button class="att-btn btn-blue" onclick="app.saveNote()">Save Note</button>
        </div>
    </div>
</div>

<!-- Quick Mark Modal -->
<div id="quickModal" class="modal-overlay quick-modal" role="dialog" aria-modal="true" aria-label="Quick attendance marking">
    <div class="modal-box">
        <div id="quickMain">
        <div class="quick-header">
            <div>
                <div class="quick-meta">
                    <span id="quickCounter" class="quick-pill">0 / 0</span>
                    <span id="quickRoll" class="quick-pill">#0</span>
                </div>
                <div id="quickName" class="quick-name" style="margin-top:6px;">Student</div>
            </div>
            <div>
                <button type="button" class="att-btn btn-outline" onclick="app.closeQuick()">Close</button>
            </div>
        </div>

        <div class="quick-actions">
            <div class="quick-primary">
                <button type="button" id="quickBtnPresent" class="att-btn btn-green" aria-label="Mark present" onclick="app.quickSet('present', true)"><span class="q-ic"></span><span>Present (P)</span></button>
                <button type="button" id="quickBtnAbsent" class="att-btn btn-red" aria-label="Mark absent" onclick="app.quickSet('absent', true)"><span class="q-ic"></span><span>Absent (A)</span></button>
            </div>
            <div class="quick-secondary">
                <button type="button" id="quickBtnLeave" class="att-btn btn-leave" aria-label="Mark leave" onclick="app.quickSet('leave', true)"><span class="q-ic"></span><span>Leave (L)</span></button>
                <button type="button" id="quickBtnLate" class="att-btn btn-late" aria-label="Mark late" onclick="app.quickSet('late', true)"><span class="q-ic"></span><span>Late (T)</span></button>
            </div>
        </div>
        <input type="hidden" id="quickStdId">

        <div class="quick-footer">
            <button type="button" class="att-btn btn-outline" onclick="app.quickPrev()">◀ Prev</button>
            <div class="quick-hint">Swipe: → Present, ← Absent, ↑ Leave, ↓ Late · Keys: P/A/L/T · Enter: next · Esc: close</div>
            <button type="button" class="att-btn btn-blue" onclick="app.quickNext()">Next ▶</button>
        </div>
        </div>

        <div id="quickComplete" style="display:none;">
            <div class="modal-header">🎉 Attendance Completed</div>
            <div class="quick-hint" style="margin: 6px 0 12px;">Summary for this class/section/date</div>
            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <span class="quick-pill" style="border-color: rgba(46, 204, 113, 0.35); background: rgba(46, 204, 113, 0.08); color:#1f8f4a;">✔ <span id="donePresent">0</span> Present</span>
                <span class="quick-pill" style="border-color: rgba(231, 76, 60, 0.35); background: rgba(231, 76, 60, 0.08); color:#b0342a;">✖ <span id="doneAbsent">0</span> Absent</span>
                <span class="quick-pill" style="border-color: rgba(243, 156, 18, 0.35); background: rgba(243, 156, 18, 0.10); color:#a86200;">🏖 <span id="doneLeave">0</span> Leave</span>
                <span class="quick-pill" style="border-color: rgba(45, 156, 219, 0.35); background: rgba(45, 156, 219, 0.08); color:#1a5a7f;">⏰ <span id="doneLate">0</span> Late</span>
            </div>
            <div class="modal-footer" style="margin-top: 16px; justify-content: space-between;">
                <button type="button" class="att-btn btn-outline" onclick="app.quickReview()">Review</button>
                <button type="button" class="att-btn btn-blue" onclick="app.closeQuick()">Submit</button>
            </div>
        </div>
    </div>
</div>

<!-- Toast -->
<div id="toast" class="toast-msg"></div>

<script>
(function($) {
    var app = {
        url: <?php echo json_encode(trailingslashit(get_template_directory_uri()) . 'inc/attendanceAjax.php'); ?>,
        state: {
            classId: '',
            sectionId: '',
            year: '',
            date: ''
        },
        autosave: {
            queueKey: 's3_att_autosave_queue_v1',
            queue: [],
            flushing: false,
            retryTimer: null,
            retryDelayMs: 1500,
            retryDelayMaxMs: 20000
        },
        students: [],
        quick: {
            idx: 0,
            open: false,
            keyHandlerBound: false,
            noteTimer: null,
            touched: {},
            autoAdvanceMs: 420
        },
        undo: {
            timer: null,
            last: null
        },
        
        init: function() {
            this.loadQueue();
            this.bindOnlineOffline();
            this.loadLocalState();
            this.bindEvents();
            this.flushQueue();
        },

        // -----------------------------
        // Autosave queue (persistent)
        // -----------------------------
        loadQueue: function() {
            try {
                var raw = localStorage.getItem(this.autosave.queueKey);
                this.autosave.queue = raw ? (JSON.parse(raw) || []) : [];
            } catch (e) {
                this.autosave.queue = [];
            }
            this.updateSyncIndicator();
        },

        saveQueue: function() {
            try {
                localStorage.setItem(this.autosave.queueKey, JSON.stringify(this.autosave.queue || []));
            } catch (e) {
                // ignore
            }
        },

        setSyncIndicator: function(state, text) {
            var $i = $('#syncIndicator');
            if ($i.length === 0) return;
            $i.removeClass('sync-saved sync-saving sync-error');
            if (state === 'saving') $i.addClass('sync-saving');
            else if (state === 'error') $i.addClass('sync-error');
            else $i.addClass('sync-saved');
            if (text) $i.text(text);
        },

        updateSyncIndicator: function() {
            if (!navigator.onLine && (this.autosave.queue || []).length > 0) {
                this.setSyncIndicator('error', 'Offline (queued)');
                return;
            }

            if ((this.autosave.queue || []).length === 0) {
                this.setSyncIndicator('saved', 'Saved');
                return;
            }

            if (this.autosave.flushing) {
                this.setSyncIndicator('saving', 'Saving…');
            } else {
                this.setSyncIndicator('saving', 'Queued (' + this.autosave.queue.length + ')');
            }
        },

        bindOnlineOffline: function() {
            var self = this;
            window.addEventListener('online', function() {
                self.autosave.retryDelayMs = 1500;
                self.updateSyncIndicator();
                self.flushQueue();
            });
            window.addEventListener('offline', function() {
                self.updateSyncIndicator();
            });
        },

        _ctxKey: function(ctx) {
            return [ctx.date, ctx.year, ctx.classId, ctx.sectionId].join('|');
        },

        enqueueSingle: function(ctx, studentId, roll, status, notes) {
            var item = {
                type: 'single',
                date: ctx.date,
                year: ctx.year,
                classId: String(ctx.classId || ''),
                sectionId: String(ctx.sectionId || 0),
                studentId: String(studentId),
                roll: String(roll || ''),
                status: String(status || 'unmarked'),
                notes: String(notes || ''),
                ts: Date.now()
            };

            // De-dupe: keep only the latest per student+context
            var key = this._ctxKey(item) + '|std:' + item.studentId;
            this.autosave.queue = (this.autosave.queue || []).filter(function(q) {
                return (q.type !== 'single') || (([q.date,q.year,q.classId,q.sectionId].join('|') + '|std:' + q.studentId) !== key);
            });

            this.autosave.queue.push(item);
            this.saveQueue();
            this.updateSyncIndicator();
            this.flushQueue();
        },

        enqueueBulk: function(ctx, status) {
            var item = {
                type: 'bulk',
                date: ctx.date,
                year: ctx.year,
                classId: String(ctx.classId || ''),
                sectionId: String(ctx.sectionId || 0),
                status: String(status || 'unmarked'),
                ts: Date.now()
            };

            var ctxKey = this._ctxKey(item);
            // Remove redundant queued singles for the same context (bulk overrides them)
            this.autosave.queue = (this.autosave.queue || []).filter(function(q) {
                var qCtx = [q.date, q.year, q.classId, q.sectionId].join('|');
                if (qCtx !== ctxKey) return true;
                return q.type !== 'single' && q.type !== 'bulk';
            });

            // Replace older bulk for the same context
            this.autosave.queue = (this.autosave.queue || []).filter(function(q) {
                return !(q.type === 'bulk' && ([q.date,q.year,q.classId,q.sectionId].join('|') === ctxKey));
            });

            this.autosave.queue.push(item);
            this.saveQueue();
            this.updateSyncIndicator();
            this.flushQueue();
        },

        flushQueue: function() {
            var self = this;
            if (self.autosave.flushing) return;
            if (!self.autosave.queue || self.autosave.queue.length === 0) {
                self.updateSyncIndicator();
                return;
            }
            if (!navigator.onLine) {
                self.updateSyncIndicator();
                return;
            }

            if (self.autosave.retryTimer) {
                clearTimeout(self.autosave.retryTimer);
                self.autosave.retryTimer = null;
            }

            var item = self.autosave.queue[0];
            self.autosave.flushing = true;
            self.updateSyncIndicator();

            var ajaxData;
            if (item.type === 'single') {
                ajaxData = {
                    mark_single: 1,
                    student_id: item.studentId,
                    status: item.status,
                    notes: item.notes,
                    roll: item.roll,
                    class_id: item.classId,
                    section_id: item.sectionId,
                    year: item.year,
                    date: item.date
                };
            } else if (item.type === 'bulk') {
                ajaxData = {
                    mark_bulk: 1,
                    status: item.status,
                    class_id: item.classId,
                    section_id: item.sectionId,
                    year: item.year,
                    date: item.date
                };
            } else {
                // Unknown item type: drop it
                self.autosave.queue.shift();
                self.saveQueue();
                self.autosave.flushing = false;
                self.flushQueue();
                return;
            }

            $.ajax({
                url: self.url,
                method: 'POST',
                data: ajaxData,
                dataType: 'json',
                timeout: 12000
            }).done(function(res) {
                var ok = res && (res.success === true || res.success === 'true');
                if (!ok) {
                    throw new Error((res && res.message) ? res.message : 'Autosave rejected');
                }

                // Success: pop and continue
                self.autosave.queue.shift();
                self.saveQueue();
                self.autosave.retryDelayMs = 1500;
                self.autosave.flushing = false;
                self.updateSyncIndicator();
                self.flushQueue();
            }).fail(function(xhr) {
                self.autosave.flushing = false;
                self.setSyncIndicator('error', 'Save failed (retrying)');

                // Exponential backoff
                var delay = self.autosave.retryDelayMs;
                self.autosave.retryDelayMs = Math.min(self.autosave.retryDelayMaxMs, Math.floor(self.autosave.retryDelayMs * 1.6));
                self.autosave.retryTimer = setTimeout(function() {
                    self.flushQueue();
                }, delay);
            });
        },

        loadLocalState: function() {
            // Restore last used filters
            var last = JSON.parse(localStorage.getItem('s3_att_last') || '{}');
            if (last.year) $('#sYear').val(last.year);
            if (last.classId) {
                $('#sClass').val(last.classId).trigger('change');
                setTimeout(function() {
                    if (last.sectionId) $('#sSection').val(last.sectionId);
                }, 800);
            }
        },

        saveLocalState: function() {
            var state = {
                year: $('#sYear').val(),
                classId: $('#sClass').val(),
                sectionId: $('#sSection').val()
            };
            localStorage.setItem('s3_att_last', JSON.stringify(state));
        },

        bindEvents: function() {
            // Class Change -> Load Sections
            $('#sClass').on('change', function() {
                var cid = $(this).val();
                var $sec = $('#sSection');
                $sec.html('<option value="0">Loading...</option>').prop('disabled', true);
                
                if (!cid) {
                    $sec.html('<option value="0">Select Class First</option>').prop('disabled', true);
                    return;
                }

                $.post(app.url, {
                    get_sections: 1,
                    class_id: cid
                }, function(res) {
                    $sec.html(res).prop('disabled', false);
                    
                    // Restore section if just loaded from state
                    var last = JSON.parse(localStorage.getItem('s3_att_last') || '{}');
                    if ($('#sClass').val() == last.classId && last.sectionId) {
                        $sec.val(last.sectionId);
                    }
                });
            });

            // Load Button
            $('#btnLoad').on('click', function(e) {
                e.preventDefault();
                app.loadStudents();
            });

            // Quick note suggestion chips
            $(document).on('click', '.quick-chip', function() {
                var note = $(this).data('note') || '';
                $('#quickNoteText').val(note).trigger('input');
            });
        },

        loadStudents: function() {
            this.state.classId = $('#sClass').val();
            this.state.sectionId = $('#sSection').val() || 0;
            this.state.year = $('#sYear').val();
            this.state.date = $('#sDate').val();

            if (!this.state.classId) {
                this.showToast('Please select a class');
                return;
            }

            this.saveLocalState();
            
            $('#studentContainer').html('<div style="grid-column:1/-1;text-align:center;">Loading...</div>');
            $('#attToolbar').hide();

            $.post(this.url, {
                fetch_attendance: 1,
                class_id: this.state.classId,
                section_id: this.state.sectionId,
                year: this.state.year,
                date: this.state.date
            }, function(res) {
                if (res && res.success) {
                    app.renderList(res.data || []);
                } else {
                    var msg = (res && res.message) ? res.message : 'Failed to load students.';
                    $('#studentContainer').html('<div class="alert alert-danger">'+msg+'</div>');
                }
            }, 'json');
        },

        renderList: function(students) {
            if (!students || students.length === 0) {
                $('#studentContainer').html('<div style="grid-column:1/-1;text-align:center;">No students found for this class.</div>');
                return;
            }

            // Cache for quick modal navigation
            this.students = students;
            if (this.quick && this.quick.open) {
                this.quick.idx = 0;
                this.quickRender();
            }

            $('#attToolbar').show();
            var html = '';
            var present = 0, absent = 0, leave = 0;

            students.forEach(function(std) {
                var isP = std.status === 'present';
                var isA = std.status === 'absent';
                var isLv = std.status === 'leave';
				var isLt = std.status === 'late';
                var noteClass = std.notes ? 'has-note' : '';
                
                if (isP) present++;
                if (isA) absent++;
                if (isLv) leave++;
				if (isLt) late++;


                html += `
                <div id="std_${std.id}" class="student-card status-${std.status}" data-roll="${std.roll}" data-id="${std.id}">
                    <div class="std-info">
                        <span class="std-roll">#${std.roll}</span>
                        <span class="std-name">${std.name}</span>
                        <small class="text-muted note-indicator">${std.notes ? '📝 ' + std.notes : ''}</small>
                    </div>
                    <div class="std-actions">
						<button type="button" class="action-btn btn-present ${isP ? 'active' : ''}" title="Present" onclick="app.mark(${std.id}, 'present')">P</button>
						<button type="button" class="action-btn btn-absent ${isA ? 'active' : ''}" title="Absent" onclick="app.mark(${std.id}, 'absent')">A</button>
						<button type="button" class="action-btn btn-leave ${isLv ? 'active' : ''}" title="Leave" onclick="app.mark(${std.id}, 'leave')">Lv</button>
						<button type="button" class="action-btn btn-late ${isLt ? 'active' : ''}" title="Late" onclick="app.mark(${std.id}, 'late')">Lt</button>
                    </div>
                </div>`;
            });

            $('#studentContainer').html(html);
            this.updateStats(students.length, present, absent, leave);
        },

        // -----------------------------
        // Quick Mark Modal
        // -----------------------------
        openQuick: function() {
            if (!this.students || this.students.length === 0) {
                this.showToast('Load students first');
                return;
            }

            this.quick.touched = {};

            // Start from first student
            var start = 0;

            this.quick.idx = start;
            this.quick.open = true;
            $('#quickModal').addClass('open');
            $('#quickMain').show();
            $('#quickComplete').hide();
            this.quickRender();
            this.bindQuickKeys();
            $('#quickBtnPresent').trigger('focus');
        },

        closeQuick: function() {
            this.quick.open = false;
            if (this.quick.noteTimer) {
                clearTimeout(this.quick.noteTimer);
                this.quick.noteTimer = null;
            }
            $('#quickModal').removeClass('open');
        },

        bindQuickKeys: function() {
            if (this.quick.keyHandlerBound) return;
            var self = this;

            // Debounced note autosave (only while modal open)
            $(document).on('input', '#quickNoteText', function() {
                if (!self.quick.open) return;
                if (self.quick.noteTimer) clearTimeout(self.quick.noteTimer);
                self.quick.noteTimer = setTimeout(function() {
                    self.quickSaveCurrent(false);
                }, 600);
            });

            $(document).on('keydown', function(e) {
                if (!self.quick.open) return;

                var key = (e.key || '').toLowerCase();
                var isTextArea = e.target && (e.target.id === 'quickNoteText');

                // Always allow Esc
                if (key === 'escape') {
                    e.preventDefault();
                    self.closeQuick();
                    return;
                }

                // If typing notes, don't steal arrow keys
                if (isTextArea) {
                    if ((e.ctrlKey || e.metaKey) && key === 'enter') {
                        e.preventDefault();
                        self.quickSaveCurrent(true);
                    }
                    return;
                }

                if (key === 'arrowright' || key === 'arrowdown') {
                    e.preventDefault();
                    self.quickNext();
                    return;
                }
                if (key === 'arrowleft' || key === 'arrowup') {
                    e.preventDefault();
                    self.quickPrev();
                    return;
                }
                if (key === 'enter') {
                    e.preventDefault();
                    self.quickNext();
                    return;
                }
                if (key === 'p') {
                    e.preventDefault();
                    self.quickSet('present', true);
                    return;
                }
                if (key === 'a') {
                    e.preventDefault();
                    self.quickSet('absent', true);
                    return;
                }
                if (key === 'l') {
                    e.preventDefault();
                    self.quickSet('leave', true);
                    return;
                }
                if (key === 't') {
                    e.preventDefault();
                    self.quickSet('late', true);
                    return;
                }
                if (key === 'n') {
                    e.preventDefault();
                    $('#quickNoteText').trigger('focus');
                    return;
                }
            });

            // Swipe gestures (modal)
            var touchStart = null;
            $(document).on('touchstart', '#quickModal .modal-box', function(e) {
                if (!self.quick.open) return;
                if (e.target && (e.target.id === 'quickNoteText')) return;
                var t = e.originalEvent.touches && e.originalEvent.touches[0];
                if (!t) return;
                touchStart = { x: t.clientX, y: t.clientY };
            });
            $(document).on('touchend', '#quickModal .modal-box', function(e) {
                if (!self.quick.open || !touchStart) return;
                var t = e.originalEvent.changedTouches && e.originalEvent.changedTouches[0];
                if (!t) return;
                var dx = t.clientX - touchStart.x;
                var dy = t.clientY - touchStart.y;
                var ax = Math.abs(dx);
                var ay = Math.abs(dy);
                touchStart = null;

                if (ax < 60 && ay < 60) return;

                if (ax >= ay) {
                    if (dx > 0) self.quickSet('present', true);
                    else self.quickSet('absent', true);
                } else {
                    if (dy < 0) self.quickSet('leave', true);
                    else self.quickSet('late', true);
                }
            });

            this.quick.keyHandlerBound = true;
        },

        quickRender: function() {
            if (!this.students || this.students.length === 0) return;
            if (this.quick.idx < 0) this.quick.idx = 0;
            if (this.quick.idx > this.students.length - 1) this.quick.idx = this.students.length - 1;

            var std = this.students[this.quick.idx];
            $('#quickStdId').val(std.id);
            $('#quickCounter').text((this.quick.idx + 1) + ' / ' + this.students.length);
            $('#quickRoll').text('#' + std.roll);
            $('#quickName').text(std.name);
            $('#quickNoteText').val(std.notes || '');

            $('#quickBtnPresent, #quickBtnAbsent, #quickBtnLeave, #quickBtnLate').removeClass('is-active');
            if (std.status === 'present') $('#quickBtnPresent').addClass('is-active');
            else if (std.status === 'absent') $('#quickBtnAbsent').addClass('is-active');
            else if (std.status === 'leave') $('#quickBtnLeave').addClass('is-active');
            else if (std.status === 'late') $('#quickBtnLate').addClass('is-active');

            this.quickUpdateNoteVisibility(std.status);
        },

        quickUpdateNoteVisibility: function(status) {
            if (status === 'absent') {
                $('#quickNoteWrap').addClass('is-visible');
            } else {
                $('#quickNoteWrap').removeClass('is-visible');
            }
        },

        quickPrev: function() {
            if (!this.students || this.students.length === 0) return;
            this.quickSaveCurrent(false);
            this.quick.idx = Math.max(0, this.quick.idx - 1);
            this.quickRender();
        },

        quickNext: function() {
            if (!this.students || this.students.length === 0) return;
            var std = this.students[this.quick.idx];

            this.quickSaveCurrent(false);
            if (this.quick.idx >= this.students.length - 1) {
                this.quickShowComplete();
                return;
            }
            this.quick.idx = Math.min(this.students.length - 1, this.quick.idx + 1);
            this.quickRender();
        },

        quickSet: function(status, autoNext) {
            if (!this.students || this.students.length === 0) return;

            var std = this.students[this.quick.idx];
            var prevStatus = std.status || 'present';

            // Haptics (mobile)
            try { if (navigator.vibrate) navigator.vibrate(10); } catch (e) {}

            this.quick.touched[String(std.id)] = true;
            this.setStudentState(std.id, std.roll, status, '');

            // Update cached student object
            std.status = status;
            std.notes = '';
            this.quickRender();

            this.showUndo(std.name, std.id, std.roll, prevStatus, '', status, '', this.quick.idx);

            if (autoNext) {
                var self = this;
                setTimeout(function() {
                    if (!self.quick.open) return;
                    self.quickNext();
                }, this.quick.autoAdvanceMs);
            }
        },

        quickShowComplete: function() {
            var present = 0, absent = 0, leave = 0, late = 0;
            (this.students || []).forEach(function(s) {
                if (s.status === 'present') present++;
                else if (s.status === 'absent') absent++;
                else if (s.status === 'leave') leave++;
                else if (s.status === 'late') late++;
            });
            $('#donePresent').text(present);
            $('#doneAbsent').text(absent);
            $('#doneLeave').text(leave);
            $('#doneLate').text(late);
            $('#quickMain').hide();
            $('#quickComplete').show();
        },

        quickReview: function() {
            $('#quickComplete').hide();
            $('#quickMain').show();
            this.quick.idx = 0;
            this.quickRender();
        },

        showUndo: function(name, studentId, roll, prevStatus, prevNotes, newStatus, newNotes, idx) {
            var self = this;
            if (self.undo.timer) {
                clearTimeout(self.undo.timer);
                self.undo.timer = null;
            }

            self.undo.last = {
                idx: idx,
                name: name,
                studentId: studentId,
                roll: roll,
                prevStatus: prevStatus || 'present',
                prevNotes: prevNotes || '',
                newStatus: newStatus || 'present',
                newNotes: newNotes || ''
            };

            $('#undoMsg').text(name + ' marked ' + newStatus);
            $('#undoBar').fadeIn(120);
            self.undo.timer = setTimeout(function() {
                $('#undoBar').fadeOut(150);
                self.undo.timer = null;
                self.undo.last = null;
            }, 4500);
        },

        undoLast: function() {
            var last = this.undo.last;
            if (!last) return;

            // Apply revert
            this.setStudentState(last.studentId, last.roll, last.prevStatus, last.prevNotes);

            // Update cached student
            if (this.students && this.students[last.idx]) {
                this.students[last.idx].status = last.prevStatus;
                this.students[last.idx].notes = last.prevNotes;
            }

            if (this.quick.open) {
                this.quick.idx = last.idx;
                $('#quickMain').show();
                $('#quickComplete').hide();
                this.quickRender();
            }

            $('#undoBar').fadeOut(150);
            if (this.undo.timer) {
                clearTimeout(this.undo.timer);
                this.undo.timer = null;
            }
            this.undo.last = null;
        },

        quickSaveCurrent: function(gotoNext) {
            if (!this.students || this.students.length === 0) return;
            var std = this.students[this.quick.idx];
            if (!std) return;

            var note = $('#quickNoteText').val() || '';
            var status = std.status || 'present';
            this.setStudentState(std.id, std.roll, status, note);
            std.notes = note;

            if (gotoNext) {
                this.quick.idx = Math.min(this.students.length - 1, this.quick.idx + 1);
                this.quickRender();
            }
        },

        setStudentState: function(id, roll, status, notes) {
            // Update card UI (if visible)
            var $card = $('#std_' + id);
            if ($card.length) {
                $card.removeClass('status-present status-absent status-leave status-late status-unmarked').addClass('status-' + status);
                $card.find('.action-btn').removeClass('active');
                if (status === 'present') $card.find('.btn-present').addClass('active');
                if (status === 'absent') $card.find('.btn-absent').addClass('active');
                if (status === 'leave') $card.find('.btn-leave').addClass('active');
                if (status === 'late') $card.find('.btn-late').addClass('active');

                $card.find('.note-indicator').text(notes ? '📝 ' + notes : '');
                if (notes) $card.find('.btn-note').addClass('has-note'); else $card.find('.btn-note').removeClass('has-note');
            }

            // Recalculate stats (cheap)
            var p = $('.status-present').length;
            var a = $('.status-absent').length;
            var l = $('.status-leave').length;
            var t = $('.status-late').length;
            $('#presentCount').text(p);
            $('#absentCount').text(a);
            $('#leaveCount').text(l);
            $('#lateCount').text(t);

            // Queue autosave
            this.enqueueSingle(this.state, id, roll, status, notes);
        },

        updateStats: function(total, p, a, l, t) {
            $('#totalCount').text(total);
            $('#presentCount').text(p);
            $('#absentCount').text(a);
            $('#leaveCount').text(l || 0);
            $('#lateCount').text(t || 0);
        },

        mark: function(id, status) {
            var $card = $('#std_' + id);
            
            // Optimistic UI Update
            $card.removeClass('status-present status-absent status-leave status-late status-unmarked').addClass('status-' + status);
            $card.find('.action-btn').removeClass('active');
            $card.find('.btn-' + status).addClass('active');

            // Recalculate stats briefly (approximate)
            var p = $('.status-present').length;
            var a = $('.status-absent').length;
            var l = $('.status-leave').length;
            var t = $('.status-late').length;
            $('#presentCount').text(p);
            $('#absentCount').text(a);
            $('#leaveCount').text(l);
            $('#lateCount').text(t);

            // Autosave (queued + retried if needed)
            var noteTxt = $card.find('.note-indicator').text().replace('📝 ', '');
            this.enqueueSingle(this.state, id, $card.data('roll'), status, noteTxt);
        },

        markBulk: function(status) {
            if (!confirm('Mark ALL students as ' + status + '?')) return;

            // Optimistic UI
            $('.student-card').removeClass('status-present status-absent status-unmarked').addClass('status-' + status);
            $('.action-btn').removeClass('active');
            $('.btn-' + status).addClass('active');

            var total = $('.student-card').length;
            this.updateStats(
                total,
                status === 'present' ? total : 0,
                status === 'absent' ? total : 0,
                status === 'leave' ? total : 0
            );

            this.enqueueBulk(this.state, status);
            app.showToast(navigator.onLine ? 'Bulk update queued' : 'Offline: bulk queued');
        },

        openNote: function(id, name, note) {
            $('#modalStdId').val(id);
            $('#modalStdName').text(name);
            $('#modalNoteText').val(note);
            $('#noteModal').addClass('open');
        },

        saveNote: function() {
            var id = $('#modalStdId').val();
            var note = $('#modalNoteText').val();
            var $card = $('#std_' + id);
            
            // Determine current status
            var status = 'present';
            if ($card.find('.btn-present').hasClass('active')) status = 'present';
            if ($card.find('.btn-absent').hasClass('active')) status = 'absent';
            if ($card.find('.btn-leave').hasClass('active')) status = 'leave';
            if ($card.find('.btn-late').hasClass('active')) status = 'late';
            
            // Update UI
            $card.find('.note-indicator').text(note ? '📝 ' + note : '');
            if (note) $card.find('.btn-note').addClass('has-note'); else $card.find('.btn-note').removeClass('has-note');
            
            $('#noteModal').removeClass('open');

            // Autosave (queued + retried if needed)
            this.enqueueSingle(this.state, id, $card.data('roll'), status, note);
            app.showToast(navigator.onLine ? 'Note queued' : 'Offline: note queued');
        },

        showToast: function(msg) {
            var $t = $('#toast');
            $t.text(msg).fadeIn();
            setTimeout(function(){ $t.fadeOut(); }, 2000);
        }
    };

    // Expose globally for inline onclick handlers
    window.app = app;

    $(document).ready(function() {
        app.init();
    });
})(jQuery);
</script>

<?php endif; ?>

<?php if ( ! is_admin() ) { get_footer(); } ?>