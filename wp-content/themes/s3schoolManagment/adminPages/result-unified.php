<?php
/**
 * Template Name: Admin Result Unified
 * 
 * Unified Result Management Interface
 * - Single filter selection (Class, Exam, Section, Group, Year, Subject)
 * - Displays all students in one table
 * - Auto-detects add vs edit mode per student
 * - Individual save per row OR bulk save all
 * - Bulk delete with checkboxes
 */

global $wpdb;
global $s3sRedux;
?>

<?php if (!is_admin()) {
    get_header(); ?>
    <div class="b-layer-main">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
<?php } ?>

<p id="theSiteURL" class="hidden"><?= get_template_directory_uri() ?></p>

<style>
/* ========================================
   MODERN UNIFIED RESULT MANAGEMENT STYLES
   ======================================== */

.result-unified-container {
    background: #f8f9fa;
    min-height: 100vh;
    padding: 20px 0;
}

.page-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    border-radius: 12px;
    margin-bottom: 30px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}

.page-header h2 {
    margin: 0;
    font-size: 28px;
    font-weight: 600;
    color: white;
}

.page-header p {
    margin: 5px 0 0 0;
    opacity: 0.9;
    font-size: 14px;
}

/* Filter Card */
.filter-card {
    background: white;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

.filter-card h4 {
    margin-top: 0;
    margin-bottom: 20px;
    color: #333;
    font-size: 18px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
}

.filter-card h4:before {
    content: "🔍";
    font-size: 20px;
}

.filter-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.filter-group {
    display: flex;
    flex-direction: column;
}

.filter-group label {
    font-size: 13px;
    font-weight: 600;
    color: #555;
    margin-bottom: 6px;
}

.filter-group select {
    padding: 10px 12px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s ease;
    background: white;
}

.filter-group select:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.filter-group select:disabled {
    background: #f5f5f5;
    cursor: not-allowed;
}

.btn-load-students {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 12px 30px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

.btn-load-students:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
}

.btn-load-students:disabled {
    background: #ccc;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

/* Action Bar */
.action-bar {
    background: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.action-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.btn-save-all, .btn-delete-selected {
    padding: 10px 24px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 14px;
}

.btn-save-all {
    background: #10b981;
    color: white;
}

.btn-save-all:hover:not(:disabled) {
    background: #059669;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
}

.btn-delete-selected {
    background: #ef4444;
    color: white;
}

.btn-delete-selected:hover:not(:disabled) {
    background: #dc2626;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
}

.btn-save-all:disabled, .btn-delete-selected:disabled {
    background: #d1d5db;
    cursor: not-allowed;
    transform: none;
}

.students-count {
    font-size: 14px;
    color: #666;
}

.students-count strong {
    color: #333;
}

/* Results Table */
.results-table-wrapper {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    overflow-x: auto;
}

.results-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 14px;
}

.results-table thead {
    position: sticky;
    top: 0;
    z-index: 10;
}

.results-table th {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 14px 10px;
    text-align: left;
    font-weight: 600;
    font-size: 13px;
    white-space: nowrap;
}

.results-table th:first-child {
    border-top-left-radius: 8px;
}

.results-table th:last-child {
    border-top-right-radius: 8px;
}

.results-table tbody tr {
    transition: all 0.3s ease;
    border-bottom: 1px solid #e5e7eb;
}

.results-table tbody tr:hover {
    background: #f9fafb;
}

.results-table td {
    padding: 12px 10px;
}

/* Row Status Colors */
.results-table tbody tr.status-add {
    background: #dbeafe;
}

.results-table tbody tr.status-edit {
    background: #fef3c7;
}

.results-table tbody tr.status-modified {
    background: #fed7aa;
}

.results-table tbody tr.status-saved {
    background: #d1fae5;
}

.results-table tbody tr.status-error {
    background: #fee2e2;
}

/* Status Badge */
.status-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-badge.badge-add {
    background: #3b82f6;
    color: white;
}

.status-badge.badge-edit {
    background: #f59e0b;
    color: white;
}

.status-badge.badge-modified {
    background: #fb923c;
    color: white;
}

.status-badge.badge-saved {
    background: #10b981;
    color: white;
}

.status-badge.badge-error {
    background: #ef4444;
    color: white;
}

/* Input Fields */
.mark-input {
    width: 70px;
    padding: 6px 8px;
    border: 2px solid #e5e7eb;
    border-radius: 6px;
    text-align: center;
    font-size: 13px;
    transition: all 0.2s ease;
}

.mark-input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.mark-input.has-error {
    border-color: #ef4444;
    background: #fee2e2;
}

.mark-input:disabled {
    background: #f3f4f6;
    cursor: not-allowed;
}

.total-display {
    font-weight: 700;
    color: #667eea;
    font-size: 15px;
}

/* Action Buttons in Table */
.btn-save-row {
    background: #10b981;
    color: white;
    border: none;
    padding: 6px 16px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-save-row:hover:not(:disabled) {
    background: #059669;
    transform: scale(1.05);
}

.btn-save-row:disabled {
    background: #d1d5db;
    cursor: not-allowed;
}

.btn-save-row.loading {
    position: relative;
    color: transparent;
}

.btn-save-row.loading:after {
    content: "";
    position: absolute;
    width: 14px;
    height: 14px;
    top: 50%;
    left: 50%;
    margin-left: -7px;
    margin-top: -7px;
    border: 2px solid white;
    border-top-color: transparent;
    border-radius: 50%;
    animation: spin 0.6s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Checkbox */
.select-checkbox {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #9ca3af;
}

.empty-state-icon {
    font-size: 64px;
    margin-bottom: 16px;
}

.empty-state h3 {
    color: #6b7280;
    font-size: 20px;
    margin-bottom: 8px;
}

.empty-state p {
    color: #9ca3af;
    font-size: 14px;
}

/* Toast Notifications */
.toast-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.toast {
    background: white;
    padding: 16px 20px;
    border-radius: 8px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 300px;
    animation: slideIn 0.3s ease;
    border-left: 4px solid;
}

.toast.toast-success {
    border-left-color: #10b981;
}

.toast.toast-error {
    border-left-color: #ef4444;
}

.toast.toast-info {
    border-left-color: #3b82f6;
}

.toast-icon {
    font-size: 24px;
}

.toast-content {
    flex: 1;
}

.toast-title {
    font-weight: 600;
    font-size: 14px;
    margin-bottom: 2px;
}

.toast-message {
    font-size: 13px;
    color: #666;
}

.toast-close {
    background: none;
    border: none;
    font-size: 20px;
    cursor: pointer;
    color: #9ca3af;
    padding: 0;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.toast-close:hover {
    color: #6b7280;
}

@keyframes slideIn {
    from {
        transform: translateX(400px);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

/* Delete Confirmation Modal */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 9998;
    animation: fadeIn 0.2s ease;
}

.modal-overlay.active {
    display: flex;
}

.modal-content {
    background: white;
    border-radius: 12px;
    padding: 30px;
    max-width: 500px;
    width: 90%;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    animation: scaleIn 0.3s ease;
}

.modal-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
}

.modal-icon {
    font-size: 32px;
}

.modal-title {
    font-size: 20px;
    font-weight: 600;
    color: #333;
    margin: 0;
}

.modal-body {
    color: #666;
    margin-bottom: 24px;
    font-size: 14px;
    line-height: 1.6;
}

.modal-footer {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.modal-btn {
    padding: 10px 24px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 14px;
}

.modal-btn-cancel {
    background: #e5e7eb;
    color: #374151;
}

.modal-btn-cancel:hover {
    background: #d1d5db;
}

.modal-btn-confirm {
    background: #ef4444;
    color: white;
}

.modal-btn-confirm:hover {
    background: #dc2626;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes scaleIn {
    from {
        transform: scale(0.9);
        opacity: 0;
    }
    to {
        transform: scale(1);
        opacity: 1;
    }
}

/* Result Entry Modal */
.result-entry-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.6);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 10000;
    animation: fadeIn 0.3s ease;
}

.result-entry-modal.active {
    display: flex;
}

.result-entry-content {
    background: white;
    border-radius: 16px;
    padding: 0;
    max-width: 700px;
    width: 95%;
    max-height: 95vh;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0,0,0,0.4);
    animation: scaleIn 0.3s ease;
    display: flex;
    flex-direction: column;
}

.result-entry-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 15px 15px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-shrink: 0;
}

.result-entry-header h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
}

.result-entry-progress {
    font-size: 12px;
    opacity: 0.9;
}

.result-entry-close {
    background: rgba(255,255,255,0.2);
    border: none;
    color: white;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    font-size: 24px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}

.result-entry-close:hover {
    background: rgba(255,255,255,0.3);
    transform: rotate(90deg);
}

.result-entry-body {
    padding: 20px 15px;
    overflow-y: auto;
    flex: 1;
    min-height: 0;
}

.student-info-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px;
    padding: 15px;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 15px;
    color: white;
}

.student-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    font-weight: 700;
    color: #667eea;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

.student-info-details {
    flex: 1;
    min-width: 0;
}

.student-roll {
    font-size: 24px;
    font-weight: 700;
    margin-bottom: 2px;
    line-height: 1.2;
}

.student-name {
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 6px;
    opacity: 0.95;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.student-meta {
    display: flex;
    gap: 12px;
    font-size: 12px;
    opacity: 0.9;
    flex-wrap: wrap;
}

.student-meta-item {
    display: flex;
    align-items: center;
    gap: 4px;
}

.student-meta-item::before {
    content: '•';
    font-size: 14px;
}

.student-meta-item:first-child::before {
    content: '';
}

.marks-form {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.marks-row {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.mark-input-group {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
}

.mark-input-label {
    font-size: 11px;
    font-weight: 700;
    color: #555;
    margin-bottom: 6px;
    text-transform: uppercase;
    white-space: nowrap;
}

.mark-max-value {
    font-size: 10px;
    color: #667eea;
    font-weight: 600;
    display: block;
    margin-top: 2px;
}

.mark-input-field {
    padding: 12px 8px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 18px;
    text-align: center;
    transition: all 0.3s ease;
    font-weight: 700;
    width: 100%;
}

.mark-input-field:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
}

.mark-input-field.has-error {
    border-color: #ef4444;
    background: #fee2e2;
}

.mark-input-field.auto-saved {
    border-color: #10b981;
    background: #d1fae5;
}

.auto-save-indicator {
    font-size: 10px;
    color: #10b981;
    margin-top: 3px;
    opacity: 0;
    transition: opacity 0.3s ease;
    text-align: center;
}

.auto-save-indicator.visible {
    opacity: 1;
}

.total-display-modal {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    padding: 12px 20px;
    border-radius: 10px;
    text-align: center;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.total-display-modal .label {
    font-size: 13px;
    font-weight: 600;
    opacity: 0.95;
}

.total-display-modal .value {
    font-size: 28px;
    font-weight: 700;
}

.result-entry-footer {
    padding: 12px 15px;
    border-top: 2px solid #e5e7eb;
    display: flex;
    gap: 8px;
    justify-content: space-between;
    flex-shrink: 0;
}

.btn-modal {
    padding: 10px 16px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 13px;
    flex: 1;
    white-space: nowrap;
}

.btn-previous {
    background: #e5e7eb;
    color: #374151;
}

.btn-previous:hover:not(:disabled) {
    background: #d1d5db;
}

.btn-skip {
    background: #f59e0b;
    color: white;
}

.btn-skip:hover:not(:disabled) {
    background: #d97706;
}

.btn-save-next {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-save-next:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

.btn-modal:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none !important;
}

/* Loading Overlay */
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255,255,255,0.9);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 9997;
}

.loading-overlay.active {
    display: flex;
}

.loading-spinner {
    width: 60px;
    height: 60px;
    border: 4px solid #e5e7eb;
    border-top-color: #667eea;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

/* Responsive */
@media screen and (max-width: 768px) {
    .result-entry-content {
        width: 98%;
        max-height: 98vh;
        border-radius: 12px;
    }
    
    .result-entry-body {
        padding: 12px 10px;
    }
    
    .student-avatar {
        width: 50px;
        height: 50px;
        font-size: 24px;
    }
    
    .student-roll {
        font-size: 20px;
    }
    
    .student-name {
        font-size: 13px;
    }
    
    .student-meta {
        font-size: 11px;
        gap: 8px;
    }
    
    .marks-row {
        gap: 8px;
    }
    
    .mark-input-group {
        min-width: calc(50% - 4px);
    }
    
    .mark-input-field {
        padding: 10px 6px;
        font-size: 16px;
    }
    
    .mark-input-label {
        font-size: 10px;
    }
    
    .total-display-modal {
        padding: 10px 15px;
    }
    
    .total-display-modal .value {
        font-size: 24px;
    }
    
    .btn-modal {
        padding: 9px 12px;
        font-size: 12px;
    }
    
    .result-entry-header {
        padding: 12px 10px;
    }
    
    .result-entry-header h3 {
        font-size: 16px;
    }
    
    .result-entry-progress {
        font-size: 11px;
    }
    
    .result-entry-footer {
        padding: 10px;
        gap: 6px;
    }
    
    .filter-row {
        grid-template-columns: 1fr;
    }
    
    .action-bar {
        flex-direction: column;
        align-items: stretch;
    }
    
    .action-buttons {
        justify-content: stretch;
    }
    
    .btn-save-all, .btn-delete-selected {
        flex: 1;
    }
    
    .page-header h2 {
        font-size: 22px;
    }
    
    .results-table {
        font-size: 12px;
    }
    
    .results-table th,
    .results-table td {
        padding: 8px 6px;
    }
    
    .mark-input {
        width: 60px;
        font-size: 12px;
    }
}

.hidden {
    display: none;
}
</style>

<div class="result-unified-container">
    <div class="container-fluid maxAdminpages">
        <!-- Page Header -->
        <div class="page-header">
            <h2>📊 Unified Result Management</h2>
            <p>Add, Edit, and Delete student results all in one place</p>
        </div>

        <!-- Filter Card -->
        <div class="filter-card">
            <h4>Filter Options</h4>
            <form id="filterForm">
                <!-- First Row: Class, Section, Group, Year/Session -->
                <div class="filter-row">
                    <div class="filter-group">
                        <label>Class *</label>
                        <select id="resultClass" name="class" required>
                            <?php
                            $classQuery = $wpdb->get_results("SELECT classid,className FROM ct_class WHERE classid IN (SELECT examClass FROM ct_exam GROUP BY examClass ORDER BY className ASC)");

                            echo "<option value=''>Select Class</option>";
                            foreach ($classQuery as $class) {
                                echo "<option value='{$class->classid}'>{$class->className}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Section</label>
                        <select id="resultSection" name="section">
                            <option value="">Select Class First</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Group</label>
                        <select id="resultGroup" name="group">
                            <option value="">All Groups</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Year/Session *</label>
                        <select id="resultYear" name="year" required disabled>
                            <option value="">Select Class First</option>
                        </select>
                    </div>
                </div>

                <!-- Second Row: Exam, Subject -->
                <div class="filter-row">
                    <div class="filter-group">
                        <label>Exam *</label>
                        <select id="resultExam" name="exam" required disabled>
                            <option value="">Select Class First</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Subject *</label>
                        <select id="resultSubject" name="subject" required disabled>
                            <option value="">Select Exam First</option>
                        </select>
                    </div>
                </div>

                <div style="text-align: right; margin-top: 10px;">
                    <button type="submit" class="btn-load-students" id="btnLoadStudents">
                        🔍 Load Students
                    </button>
                </div>
            </form>
        </div>

        <!-- Action Bar (hidden initially) -->
        <div class="action-bar hidden" id="actionBar">
            <div class="students-count" id="studentsCount">
                <strong>0</strong> students loaded
            </div>
            <div class="action-buttons">
                <button class="btn-save-all" id="btnOpenModal" disabled>
                    ✏️ Enter Results (Modal)
                </button>
                <button class="btn-delete-selected" id="btnDeleteSelected" disabled>
                    🗑️ Delete Selected
                </button>
            </div>
        </div>

        <!-- Results Table (hidden initially) -->
        <div class="results-table-wrapper hidden" id="resultsTableWrapper">
            <div class="table-responsive">
                <table class="results-table" id="resultsTable">
                    <thead>
                        <tr>
                            <th>
                                <input type="checkbox" class="select-checkbox" id="selectAll" title="Select All">
                            </th>
                            <th>Roll</th>
                            <th>Name</th>
                            <th>Group</th>
                            <th>Section</th>
                            <th>Status</th>
                            <th class="mark-column mark-cq">CQ (<span class="max-cq">0</span>)</th>
                            <th class="mark-column mark-mcq">MCQ (<span class="max-mcq">0</span>)</th>
                            <th class="mark-column mark-prac">Prac (<span class="max-prac">0</span>)</th>
                            <th class="mark-column mark-ca">CA (<span class="max-ca">0</span>)</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody id="resultsTableBody">
                        <!-- Rows will be populated by JavaScript -->
                    </tbody>
                </table>
            </div>
            
            <!-- Empty State -->
            <div class="empty-state hidden" id="emptyState">
                <div class="empty-state-icon">📝</div>
                <h3>No Students Found</h3>
                <p>No students are enrolled for the selected criteria.</p>
            </div>
        </div>
    </div>
</div>

<!-- Toast Container -->
<div class="toast-container" id="toastContainer"></div>

<!-- Delete Confirmation Modal -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal-content">
        <div class="modal-header">
            <span class="modal-icon">⚠️</span>
            <h3 class="modal-title">Confirm Delete</h3>
        </div>
        <div class="modal-body">
            Are you sure you want to delete the results for <strong id="deleteCount">0</strong> selected student(s)? This action cannot be undone.
        </div>
        <div class="modal-footer">
            <button class="modal-btn modal-btn-cancel" id="btnCancelDelete">Cancel</button>
            <button class="modal-btn modal-btn-confirm" id="btnConfirmDelete">Delete</button>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-spinner"></div>
</div>

<!-- Result Entry Modal -->
<div class="result-entry-modal" id="resultEntryModal">
    <div class="result-entry-content">
        <div class="result-entry-header">
            <div>
                <h3>📝 Enter Result</h3>
                <div class="result-entry-progress" id="modalProgress">Student 1 of 0</div>
            </div>
            <button class="result-entry-close" id="btnCloseModal">×</button>
        </div>
        
        <div class="result-entry-body">
            <div class="student-info-card">
                <div class="student-avatar" id="modalStudentAvatar">1</div>
                <div class="student-info-details">
                    <div class="student-roll" id="modalStudentRoll">Roll: -</div>
                    <div class="student-name" id="modalStudentName">-</div>
                    <div class="student-meta">
                        <span class="student-meta-item" id="modalStudentSection">-</span>
                        <span class="student-meta-item" id="modalStudentGroup">-</span>
                    </div>
                </div>
            </div>
            
            <div class="marks-form" id="modalMarksForm">
                <div class="marks-row">
                    <div class="mark-input-group modal-mark-cq">
                        <label class="mark-input-label">
                            CQ
                            <span class="mark-max-value">Max: <span id="modalMaxCq">0</span></span>
                        </label>
                        <input type="text" class="mark-input-field" id="modalInputCq" data-field="cq" placeholder="0">
                        <div class="auto-save-indicator" id="autoSaveCq">✓ Saved</div>
                    </div>
                    
                    <div class="mark-input-group modal-mark-mcq">
                        <label class="mark-input-label">
                            MCQ
                            <span class="mark-max-value">Max: <span id="modalMaxMcq">0</span></span>
                        </label>
                        <input type="text" class="mark-input-field" id="modalInputMcq" data-field="mcq" placeholder="0">
                        <div class="auto-save-indicator" id="autoSaveMcq">✓ Saved</div>
                    </div>
                    
                    <div class="mark-input-group modal-mark-prac">
                        <label class="mark-input-label">
                            Practical
                            <span class="mark-max-value">Max: <span id="modalMaxPrac">0</span></span>
                        </label>
                        <input type="text" class="mark-input-field" id="modalInputPrac" data-field="prac" placeholder="0">
                        <div class="auto-save-indicator" id="autoSavePrac">✓ Saved</div>
                    </div>
                    
                    <div class="mark-input-group modal-mark-ca">
                        <label class="mark-input-label">
                            CA
                            <span class="mark-max-value">Max: <span id="modalMaxCa">0</span></span>
                        </label>
                        <input type="text" class="mark-input-field" id="modalInputCa" data-field="ca" placeholder="0">
                        <div class="auto-save-indicator" id="autoSaveCa">✓ Saved</div>
                    </div>
                
                    <div class="total-display-modal">
                        <span class="label">Total:</span>
                        <span class="value" id="modalTotalDisplay">0</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="result-entry-footer">
            <button class="btn-modal btn-previous" id="btnModalPrevious">← Previous</button>
            <button class="btn-modal btn-skip" id="btnModalSkip">Skip →</button>
            <button class="btn-modal btn-save-next" id="btnModalSaveNext">Save & Next →</button>
        </div>
    </div>
</div>

<script>
(function($) {
    'use strict';

    // State management
    let studentsData = [];
    let subjectConfig = {
        cq: 0,
        mcq: 0,
        prac: 0,
        ca: 0
    };
    let modifiedRows = new Set();
    let selectedForDelete = new Set();
    let autoSaveTimeouts = {};
    let currentModalIndex = 0;
    let modalStudentsData = [];

    const $siteUrl = $('#theSiteURL').text();
    const AUTO_SAVE_DELAY = 1500; // 1.5 seconds debounce

    // ==========================================
    // FILTER CASCADING LOGIC
    // ==========================================

    $('#resultClass').change(function() {
        const classId = $(this).val();
        
        if (!classId) return;

        // Load Exams
        $.ajax({
            url: $siteUrl + "/inc/ajaxAction.php",
            method: "POST",
            data: { class: classId, type: 'getExams' },
            dataType: "html"
        }).done(function(msg) {
            $("#resultExam").html(msg).prop('disabled', false);
        });

        // Load Years
        $.ajax({
            url: $siteUrl + "/inc/ajaxAction.php",
            method: "POST",
            data: { class: classId, type: 'getYears' },
            dataType: "html"
        }).done(function(msg) {
            $("#resultYear").html(msg).prop('disabled', false);
        });

        // Load Sections
        $.ajax({
            url: $siteUrl + "/inc/ajaxAction.php",
            method: "POST",
            data: { class: classId, type: 'getSection' },
            dataType: "html"
        }).done(function(msg) {
            $("#resultSection").html(msg).prop('disabled', false);
        });

        // Load Groups
        $.ajax({
            url: $siteUrl + "/inc/ajaxAction.php",
            method: "POST",
            data: { class: classId, type: 'getGroupsByClass' },
            dataType: "html"
        }).done(function(msg) {
            $("#resultGroup").html(msg).prop('disabled', false);
        });
    });

    $('#resultExam, #resultGroup').change(function() {
        const exam = $('#resultExam').val();
        const group = $('#resultGroup').val();

        if (exam) {
            $.ajax({
                url: $siteUrl + "/inc/ajaxAction.php",
                method: "POST",
                data: { 
                    exam: exam,
                    group: group,
                    type: 'getExamSubject'
                },
                dataType: "html"
            }).done(function(msg) {
                $("#resultSubject").html(msg).prop('disabled', false);
            });
        }
    });

    // ==========================================
    // LOAD STUDENTS
    // ==========================================

    $('#filterForm').submit(function(e) {
        e.preventDefault();

        const formData = {
            class: $('#resultClass').val(),
            exam: $('#resultExam').val(),
            section: $('#resultSection').val() || '',
            group: $('#resultGroup').val() || '',
            year: $('#resultYear').val(),
            subject: $('#resultSubject').val(),
            type: 'load_students'
        };

        // Validate required fields
        if (!formData.class || !formData.exam || !formData.year || !formData.subject) {
            showToastMessage('error', 'Please fill all required fields');
            return;
        }

        $('#loadingOverlay').addClass('active');

        $.ajax({
            url: $siteUrl + '/inc/ajaxAction.php',
            method: 'POST',
            data: formData,
            dataType: 'json'
        }).done(function(response) {
            if (response.success) {
                studentsData = response.data.students;
                subjectConfig = response.data.subject_config;
                renderStudentsTable();
                showToastMessage('success', `Loaded ${studentsData.length} students`);
            } else {
                showToastMessage('error', response.message || 'Failed to load students');
            }
        }).fail(function() {
            showToastMessage('error', 'Server error. Please try again.');
        }).always(function() {
            $('#loadingOverlay').removeClass('active');
        });
    });

    // ==========================================
    // RENDER STUDENTS TABLE
    // ==========================================

    function renderStudentsTable() {
        const $tbody = $('#resultsTableBody');
        $tbody.empty();

        // Update subject max values in header
        $('.max-cq').text(subjectConfig.cq);
        $('.max-mcq').text(subjectConfig.mcq);
        $('.max-prac').text(subjectConfig.prac);
        $('.max-ca').text(subjectConfig.ca);

        // Show/hide columns based on subject config
        $('.mark-cq').toggle(subjectConfig.cq > 0);
        $('.mark-mcq').toggle(subjectConfig.mcq > 0);
        $('.mark-prac').toggle(subjectConfig.prac > 0);
        $('.mark-ca').toggle(subjectConfig.ca > 0);

        if (studentsData.length === 0) {
            $('#emptyState').removeClass('hidden');
            $('#resultsTableWrapper').addClass('hidden');
            $('#actionBar').addClass('hidden');
            return;
        }

        $('#emptyState').addClass('hidden');
        $('#resultsTableWrapper').removeClass('hidden');
        $('#actionBar').removeClass('hidden');
        $('#studentsCount').html(`<strong>${studentsData.length}</strong> students loaded`);

        studentsData.forEach((student, index) => {
            const mode = student.result_id ? 'edit' : 'add';
            const statusClass = `status-${mode}`;
            const statusBadge = mode === 'add' ? 'badge-add' : 'badge-edit';
            const statusText = mode === 'add' ? 'New' : 'Edit';
            const showCheckbox = mode === 'edit';
            const resultIdAttr = student.result_id ? student.result_id : '';

            const row = `
                <tr class="${statusClass}" data-student-id="${student.student_id}" data-result-id="${resultIdAttr}" data-mode="${mode}">
                    <td>
                        ${showCheckbox ? `<input type="checkbox" class="select-checkbox row-select" data-student-id="${student.student_id}">` : ''}
                    </td>
                    <td>${student.roll}</td>
                    <td>${student.name}</td>
                    <td>${student.group || '-'}</td>
                    <td>${student.section || '-'}</td>
                    <td><span class="status-badge ${statusBadge} row-status">${statusText}</span></td>
                    <td class="mark-cq" style="display: ${subjectConfig.cq > 0 ? 'table-cell' : 'none'}">
                        <input type="text" class="mark-input mark-cq-input" data-max="${subjectConfig.cq}" value="${student.marks.cq || ''}" data-field="cq">
                    </td>
                    <td class="mark-mcq" style="display: ${subjectConfig.mcq > 0 ? 'table-cell' : 'none'}">
                        <input type="text" class="mark-input mark-mcq-input" data-max="${subjectConfig.mcq}" value="${student.marks.mcq || ''}" data-field="mcq">
                    </td>
                    <td class="mark-prac" style="display: ${subjectConfig.prac > 0 ? 'table-cell' : 'none'}">
                        <input type="text" class="mark-input mark-prac-input" data-max="${subjectConfig.prac}" value="${student.marks.prac || ''}" data-field="prac">
                    </td>
                    <td class="mark-ca" style="display: ${subjectConfig.ca > 0 ? 'table-cell' : 'none'}">
                        <input type="text" class="mark-input mark-ca-input" data-max="${subjectConfig.ca}" value="${student.marks.ca || ''}" data-field="ca">
                    </td>
                    <td><span class="total-display">${calculateTotal(student.marks)}</span></td>
                </tr>
            `;
            $tbody.append(row);
        });

        attachTableEvents();
    }

    // ==========================================
    // TABLE EVENTS
    // ==========================================

    function attachTableEvents() {
        // Mark input change detection with auto-save
        $('.mark-input').on('input', function() {
            const $input = $(this);
            const $row = $input.closest('tr');
            const value = $input.val();
            const max = parseFloat($input.data('max'));
            const studentId = $row.data('student-id');

            // Validation
            if (value !== '' && value !== 'A' && value !== 'a') {
                const numValue = parseFloat(value);
                if (isNaN(numValue) || numValue > max || numValue < 0) {
                    $input.addClass('has-error');
                    return;
                } else {
                    $input.removeClass('has-error');
                }
            } else {
                $input.removeClass('has-error');
            }

            // Update total
            updateRowTotal($row);

            // Mark as modified
            markRowAsModified($row);

            // Clear existing timeout for this student
            if (autoSaveTimeouts[studentId]) {
                clearTimeout(autoSaveTimeouts[studentId]);
            }

            // Set new timeout for auto-save
            autoSaveTimeouts[studentId] = setTimeout(function() {
                autoSaveRow($row);
            }, AUTO_SAVE_DELAY);
        });

        // Row selection
        $('.row-select').change(function() {
            const studentId = $(this).data('student-id');
            if ($(this).is(':checked')) {
                selectedForDelete.add(studentId);
            } else {
                selectedForDelete.delete(studentId);
            }
            updateActionButtons();
        });

        // Select all
        $('#selectAll').change(function() {
            const isChecked = $(this).is(':checked');
            $('.row-select').prop('checked', isChecked).trigger('change');
        });
    }

    // ==========================================
    // HELPER FUNCTIONS
    // ==========================================

    function calculateTotal(marks) {
        let total = 0;
        ['cq', 'mcq', 'prac', 'ca'].forEach(field => {
            const val = marks[field];
            if (val && val !== 'A' && val !== 'a') {
                const num = parseFloat(val);
                if (!isNaN(num)) total += num;
            }
        });
        return total;
    }

    function updateRowTotal($row) {
        const marks = {
            cq: $row.find('.mark-cq-input').val(),
            mcq: $row.find('.mark-mcq-input').val(),
            prac: $row.find('.mark-prac-input').val(),
            ca: $row.find('.mark-ca-input').val()
        };
        const total = calculateTotal(marks);
        $row.find('.total-display').text(total);
    }

    function markRowAsModified($row) {
        const studentId = $row.data('student-id');
        modifiedRows.add(studentId);
        
        $row.removeClass('status-add status-edit status-saved status-error').addClass('status-modified');
        $row.find('.row-status').removeClass('badge-add badge-edit badge-saved badge-error').addClass('badge-modified').text('Modified');
        
        updateActionButtons();
    }

    function updateActionButtons() {
        $('#btnOpenModal').prop('disabled', studentsData.length === 0);
        $('#btnDeleteSelected').prop('disabled', selectedForDelete.size === 0);
        
        // Update count
        let countText = `<strong>${studentsData.length}</strong> students loaded`;
        if (modifiedRows.size > 0) {
            countText += `, <strong style="color: #fb923c">${modifiedRows.size}</strong> modified`;
        }
        if (selectedForDelete.size > 0) {
            countText += `, <strong style="color: #ef4444">${selectedForDelete.size}</strong> selected`;
        }
        $('#studentsCount').html(countText);
    }

    // ==========================================
    // AUTO-SAVE FUNCTIONALITY
    // ==========================================

    function autoSaveRow($row) {
        // Validate inputs
        if ($row.find('.mark-input.has-error').length > 0) {
            return;
        }

        const studentId = $row.data('student-id');
        const resultId = $row.data('result-id');
        const mode = $row.data('mode');

        const marks = {
            cq: $row.find('.mark-cq-input').val(),
            mcq: $row.find('.mark-mcq-input').val(),
            prac: $row.find('.mark-prac-input').val(),
            ca: $row.find('.mark-ca-input').val()
        };

        // Check if at least one VISIBLE mark is entered
        const visibleMarks = [];
        if (subjectConfig.cq > 0) visibleMarks.push(marks.cq);
        if (subjectConfig.mcq > 0) visibleMarks.push(marks.mcq);
        if (subjectConfig.prac > 0) visibleMarks.push(marks.prac);
        if (subjectConfig.ca > 0) visibleMarks.push(marks.ca);
        
        const hasMarks = visibleMarks.some(val => val !== '' && val !== null && val !== undefined);
        if (!hasMarks) {
            return;
        }

        const saveData = {
            type: 'save_result',
            student_id: studentId,
            mode: mode,
            class: $('#resultClass').val(),
            exam: $('#resultExam').val(),
            section: $('#resultSection').val() || '',
            group: $('#resultGroup').val() || '',
            year: $('#resultYear').val(),
            subject: $('#resultSubject').val(),
            marks: marks
        };
        
        // Only send result_id if it exists
        if (resultId && resultId !== '' && resultId !== 'null') {
            saveData.result_id = resultId;
        }

        $.ajax({
            url: $siteUrl + '/inc/ajaxAction.php',
            method: 'POST',
            data: saveData,
            dataType: 'json'
        }).done(function(response) {
            if (response.success) {
                $row.removeClass('status-add status-edit status-modified status-error').addClass('status-saved');
                $row.find('.row-status').removeClass('badge-add badge-edit badge-modified badge-error').addClass('badge-saved').text('Saved');
                $row.data('result-id', response.data.result_id);
                $row.data('mode', 'edit');
                
                // Add checkbox if it was add mode
                if (mode === 'add' && !$row.find('.row-select').length) {
                    $row.find('td:first').html(`<input type="checkbox" class="select-checkbox row-select" data-student-id="${studentId}">`);
                    $row.find('.row-select').change(function() {
                        const sid = $(this).data('student-id');
                        if ($(this).is(':checked')) {
                            selectedForDelete.add(sid);
                        } else {
                            selectedForDelete.delete(sid);
                        }
                        updateActionButtons();
                    });
                }
                
                modifiedRows.delete(studentId);
                updateActionButtons();
            } else {
                $row.removeClass('status-add status-edit status-modified status-saved').addClass('status-error');
                $row.find('.row-status').removeClass('badge-add badge-edit badge-modified badge-saved').addClass('badge-error').text('Error');
            }
        });
    }

    // ==========================================
    // SAVE OPERATIONS
    // ==========================================

    function saveRow($row, $btn) {
        // Validate inputs
        if ($row.find('.mark-input.has-error').length > 0) {
            showToastMessage('error', 'Please fix validation errors');
            return;
        }

        const studentId = $row.data('student-id');
        const resultId = $row.data('result-id');
        const mode = $row.data('mode');

        const marks = {
            cq: $row.find('.mark-cq-input').val(),
            mcq: $row.find('.mark-mcq-input').val(),
            prac: $row.find('.mark-prac-input').val(),
            ca: $row.find('.mark-ca-input').val()
        };

        // Check if at least one VISIBLE mark is entered
        const visibleMarks = [];
        if (subjectConfig.cq > 0) visibleMarks.push(marks.cq);
        if (subjectConfig.mcq > 0) visibleMarks.push(marks.mcq);
        if (subjectConfig.prac > 0) visibleMarks.push(marks.prac);
        if (subjectConfig.ca > 0) visibleMarks.push(marks.ca);
        
        const hasMarks = visibleMarks.some(val => val !== '' && val !== null && val !== undefined);
        if (!hasMarks) {
            showToastMessage('error', 'Please enter at least one mark');
            return;
        }

        $btn.prop('disabled', true).addClass('loading');

        const saveData = {
            type: 'save_result',
            student_id: studentId,
            mode: mode,
            class: $('#resultClass').val(),
            exam: $('#resultExam').val(),
            section: $('#resultSection').val() || '',
            group: $('#resultGroup').val() || '',
            year: $('#resultYear').val(),
            subject: $('#resultSubject').val(),
            marks: marks
        };
        
        // Only send result_id if it exists
        if (resultId && resultId !== '' && resultId !== 'null') {
            saveData.result_id = resultId;
        }

        $.ajax({
            url: $siteUrl + '/inc/ajaxAction.php',
            method: 'POST',
            data: saveData,
            dataType: 'json'
        }).done(function(response) {
            if (response.success) {
                $row.removeClass('status-add status-edit status-modified status-error').addClass('status-saved');
                $row.find('.row-status').removeClass('badge-add badge-edit badge-modified badge-error').addClass('badge-saved').text('Saved');
                $row.data('result-id', response.data.result_id);
                $row.data('mode', 'edit');
                
                // Add checkbox if it was add mode
                if (mode === 'add' && !$row.find('.row-select').length) {
                    $row.find('td:first').html(`<input type="checkbox" class="select-checkbox row-select" data-student-id="${studentId}">`);
                    $row.find('.row-select').change(function() {
                        const sid = $(this).data('student-id');
                        if ($(this).is(':checked')) {
                            selectedForDelete.add(sid);
                        } else {
                            selectedForDelete.delete(sid);
                        }
                        updateActionButtons();
                    });
                }
                
                modifiedRows.delete(studentId);
                updateActionButtons();
                showToastMessage('success', 'Result saved successfully');
            } else {
                $row.removeClass('status-add status-edit status-modified status-saved').addClass('status-error');
                $row.find('.row-status').removeClass('badge-add badge-edit badge-modified badge-saved').addClass('badge-error').text('Error');
                showToastMessage('error', response.message || 'Failed to save');
            }
        }).fail(function() {
            showToastMessage('error', 'Server error. Please try again.');
        }).always(function() {
            $btn.prop('disabled', false).removeClass('loading');
        });
    }

    // ==========================================
    // MODAL FUNCTIONALITY
    // ==========================================

    $('#btnOpenModal').click(function() {
        if (studentsData.length === 0) return;
        
        modalStudentsData = [...studentsData];
        currentModalIndex = 0;
        openModal();
    });

    function openModal() {
        $('#resultEntryModal').addClass('active');
        loadModalStudent(currentModalIndex);
    }

    function closeModal() {
        $('#resultEntryModal').removeClass('active');
    }

    $('#btnCloseModal').click(closeModal);

    function loadModalStudent(index) {
        if (index < 0 || index >= modalStudentsData.length) return;

        const student = modalStudentsData[index];
        currentModalIndex = index;

        // Update progress
        $('#modalProgress').text(`Student ${index + 1} of ${modalStudentsData.length}`);

        // Update student info
        $('#modalStudentAvatar').text(student.roll);
        $('#modalStudentRoll').text('Roll: ' + student.roll);
        $('#modalStudentName').text(student.name);
        $('#modalStudentSection').text('Section: ' + (student.section || 'N/A'));
        $('#modalStudentGroup').text('Group: ' + (student.group || 'N/A'));

        // Update max values
        $('#modalMaxCq').text(subjectConfig.cq);
        $('#modalMaxMcq').text(subjectConfig.mcq);
        $('#modalMaxPrac').text(subjectConfig.prac);
        $('#modalMaxCa').text(subjectConfig.ca);

        // Show/hide fields
        $('.modal-mark-cq').toggle(subjectConfig.cq > 0);
        $('.modal-mark-mcq').toggle(subjectConfig.mcq > 0);
        $('.modal-mark-prac').toggle(subjectConfig.prac > 0);
        $('.modal-mark-ca').toggle(subjectConfig.ca > 0);

        // Set field max attributes
        $('#modalInputCq').attr('data-max', subjectConfig.cq);
        $('#modalInputMcq').attr('data-max', subjectConfig.mcq);
        $('#modalInputPrac').attr('data-max', subjectConfig.prac);
        $('#modalInputCa').attr('data-max', subjectConfig.ca);

        // Load marks
        $('#modalInputCq').val(student.marks.cq || '').removeClass('has-error auto-saved');
        $('#modalInputMcq').val(student.marks.mcq || '').removeClass('has-error auto-saved');
        $('#modalInputPrac').val(student.marks.prac || '').removeClass('has-error auto-saved');
        $('#modalInputCa').val(student.marks.ca || '').removeClass('has-error auto-saved');

        // Hide auto-save indicators
        $('.auto-save-indicator').removeClass('visible');

        // Update total
        updateModalTotal();

        // Update button states
        $('#btnModalPrevious').prop('disabled', index === 0);
        $('#btnModalSkip, #btnModalSaveNext').prop('disabled', index >= modalStudentsData.length - 1);

        // Focus first visible input
        setTimeout(() => {
            if (subjectConfig.cq > 0) $('#modalInputCq').focus();
            else if (subjectConfig.mcq > 0) $('#modalInputMcq').focus();
            else if (subjectConfig.prac > 0) $('#modalInputPrac').focus();
            else if (subjectConfig.ca > 0) $('#modalInputCa').focus();
        }, 100);
    }

    function updateModalTotal() {
        const marks = {
            cq: $('#modalInputCq').val(),
            mcq: $('#modalInputMcq').val(),
            prac: $('#modalInputPrac').val(),
            ca: $('#modalInputCa').val()
        };
        const total = calculateTotal(marks);
        $('#modalTotalDisplay').text(total);
    }

    // Modal input change with auto-save
    $('.mark-input-field').on('input', function() {
        const $input = $(this);
        const value = $input.val();
        const max = parseFloat($input.attr('data-max'));
        const field = $input.attr('data-field');

        // Validation
        if (value !== '' && value !== 'A' && value !== 'a') {
            const numValue = parseFloat(value);
            if (isNaN(numValue) || numValue > max || numValue < 0) {
                $input.addClass('has-error');
                return;
            } else {
                $input.removeClass('has-error');
            }
        } else {
            $input.removeClass('has-error');
        }

        // Update total
        updateModalTotal();

        // Clear existing timeout
        if (autoSaveTimeouts['modal_' + field]) {
            clearTimeout(autoSaveTimeouts['modal_' + field]);
        }

        // Set new timeout for auto-save
        autoSaveTimeouts['modal_' + field] = setTimeout(function() {
            saveModalStudent(false);
            $input.addClass('auto-saved');
            $('#autoSave' + field.charAt(0).toUpperCase() + field.slice(1)).addClass('visible');
            setTimeout(() => {
                $('#autoSave' + field.charAt(0).toUpperCase() + field.slice(1)).removeClass('visible');
            }, 2000);
        }, AUTO_SAVE_DELAY);
    });

    function saveModalStudent(showToast = true) {
        const student = modalStudentsData[currentModalIndex];
        
        const marks = {
            cq: $('#modalInputCq').val(),
            mcq: $('#modalInputMcq').val(),
            prac: $('#modalInputPrac').val(),
            ca: $('#modalInputCa').val()
        };

        const saveData = {
            type: 'save_result',
            student_id: student.student_id,
            mode: student.result_id ? 'edit' : 'add',
            class: $('#resultClass').val(),
            exam: $('#resultExam').val(),
            section: $('#resultSection').val() || '',
            group: $('#resultGroup').val() || '',
            year: $('#resultYear').val(),
            subject: $('#resultSubject').val(),
            marks: marks
        };
        
        // Only send result_id if it exists
        if (student.result_id && student.result_id !== '' && student.result_id !== 'null') {
            saveData.result_id = student.result_id;
        }

        $.ajax({
            url: $siteUrl + '/inc/ajaxAction.php',
            method: 'POST',
            data: saveData,
            dataType: 'json'
        }).done(function(response) {
            if (response.success) {
                // Update student data
                student.result_id = response.data.result_id;
                student.marks = marks;

                // Update corresponding table row
                const $row = $(`tr[data-student-id="${student.student_id}"]`);
                if ($row.length) {
                    $row.removeClass('status-add status-edit status-modified status-error').addClass('status-saved');
                    $row.find('.row-status').removeClass('badge-add badge-edit badge-modified badge-error').addClass('badge-saved').text('Saved');
                    $row.data('result-id', response.data.result_id);
                    $row.data('mode', 'edit');
                    
                    // Update marks in table
                    $row.find('.mark-cq-input').val(marks.cq);
                    $row.find('.mark-mcq-input').val(marks.mcq);
                    $row.find('.mark-prac-input').val(marks.prac);
                    $row.find('.mark-ca-input').val(marks.ca);
                    updateRowTotal($row);
                }

                if (showToast) {
                    showToastMessage('success', 'Result saved successfully');
                }
            } else if (showToast) {
                showToastMessage('error', response.message || 'Failed to save');
            }
        });
    }

    $('#btnModalPrevious').click(function() {
        if (currentModalIndex > 0) {
            loadModalStudent(currentModalIndex - 1);
        }
    });

    $('#btnModalSkip').click(function() {
        if (currentModalIndex < modalStudentsData.length - 1) {
            loadModalStudent(currentModalIndex + 1);
        } else {
            closeModal();
            showToastMessage('info', 'All students completed');
        }
    });

    $('#btnModalSaveNext').click(function() {
        // Clear any pending auto-saves
        Object.keys(autoSaveTimeouts).forEach(key => {
            if (key.startsWith('modal_')) {
                clearTimeout(autoSaveTimeouts[key]);
            }
        });

        // Save current student
        saveModalStudent(true);

        // Move to next
        setTimeout(() => {
            if (currentModalIndex < modalStudentsData.length - 1) {
                loadModalStudent(currentModalIndex + 1);
            } else {
                closeModal();
                showToastMessage('success', 'All students completed!');
            }
        }, 300);
    });

    // ==========================================
    // DELETE OPERATIONS
    // ==========================================

    $('#btnDeleteSelected').click(function() {
        if (selectedForDelete.size === 0) return;

        $('#deleteCount').text(selectedForDelete.size);
        $('#deleteModal').addClass('active');
    });

    $('#btnCancelDelete').click(function() {
        $('#deleteModal').removeClass('active');
    });

    $('#btnConfirmDelete').click(function() {
        const studentIds = Array.from(selectedForDelete);

        $('#loadingOverlay').addClass('active');
        $('#deleteModal').removeClass('active');

        const deleteData = {
            type: 'delete_results',
            student_ids: studentIds,
            class: $('#resultClass').val(),
            exam: $('#resultExam').val(),
            year: $('#resultYear').val(),
            subject: $('#resultSubject').val()
        };

        $.ajax({
            url: $siteUrl + '/inc/ajaxAction.php',
            method: 'POST',
            data: deleteData,
            dataType: 'json'
        }).done(function(response) {
            if (response.success) {
                // Remove deleted rows from table
                studentIds.forEach(sid => {
                    $(`tr[data-student-id="${sid}"]`).fadeOut(300, function() {
                        $(this).remove();
                        
                        // Update studentsData
                        studentsData = studentsData.filter(s => s.student_id != sid);
                        
                        // Reset this student to add mode if they're still in the list
                        const student = studentsData.find(s => s.student_id == sid);
                        if (student) {
                            student.result_id = null;
                            student.marks = { cq: '', mcq: '', prac: '', ca: '' };
                        }
                    });
                });
                
                selectedForDelete.clear();
                $('#selectAll').prop('checked', false);
                updateActionButtons();
                
                showToastMessage('success', `Deleted ${studentIds.length} result(s)`);
                
                // Re-render table if needed
                setTimeout(() => {
                    if (studentsData.length === 0) {
                        $('#emptyState').removeClass('hidden');
                        $('#resultsTableWrapper').addClass('hidden');
                        $('#actionBar').addClass('hidden');
                    }
                }, 350);
            } else {
                showToastMessage('error', response.message || 'Failed to delete');
            }
        }).fail(function() {
            showToastMessage('error', 'Server error. Please try again.');
        }).always(function() {
            $('#loadingOverlay').removeClass('active');
        });
    });

    // ==========================================
    // TOAST NOTIFICATIONS
    // ==========================================

    function showToastMessage(type, message) {
        const icons = {
            success: '✅',
            error: '❌',
            info: 'ℹ️'
        };

        const titles = {
            success: 'Success',
            error: 'Error',
            info: 'Info'
        };

        const toast = $(`
            <div class="toast toast-${type}">
                <span class="toast-icon">${icons[type]}</span>
                <div class="toast-content">
                    <div class="toast-title">${titles[type]}</div>
                    <div class="toast-message">${message}</div>
                </div>
                <button class="toast-close">×</button>
            </div>
        `);

        $('#toastContainer').append(toast);

        toast.find('.toast-close').click(function() {
            toast.fadeOut(200, function() {
                toast.remove();
            });
        });

        setTimeout(function() {
            toast.fadeOut(200, function() {
                toast.remove();
            });
        }, 5000);
    }
})(jQuery);
</script>

<?php if (!is_admin()) { ?>
                </div>
            </div>
        </div>
    </div>
<?php get_footer();
} ?>
