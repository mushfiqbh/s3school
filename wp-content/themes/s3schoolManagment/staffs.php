<?php
/*
Template Name: Staff Page
*/

get_header();
?>

<style>
    .staff-directory {
        padding: 40px 30px;
        margin-bottom: 40px;
        background: #f8fafc;
        border-radius: 24px;
        box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.1);
    }

    .staff-directory__heading {
        text-align: center;
        max-width: 560px;
        margin: 0 auto 35px;
    }

    .staff-directory__heading h3 {
        font-size: 28px;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 12px;
    }

    .staff-directory__heading p {
        margin: 0;
        color: #64748b;
        font-size: 15px;
        line-height: 1.6;
    }

    .staff-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(100px, 1fr));
        gap: 24px;
    }

    @media (max-width: 900px) {
        .staff-grid {
            grid-template-columns: repeat(3, minmax(100px, 1fr));
        }
    }

    @media (max-width: 600px) {
        .staff-grid {
            grid-template-columns: repeat(2, minmax(100px, 1fr));
        }
    }

    .staff-card-link {
        display: block;
        height: 100%;
        text-decoration: none;
        color: inherit;
    }

    .staff-card {
        height: 100%;
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 10px 25px rgba(15, 23, 42, 0.08);
        overflow: hidden;
        position: relative;
        display: flex;
        flex-direction: column;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .staff-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 4px;
        background: linear-gradient(90deg, #f97316, #facc15);
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .staff-card-link:hover .staff-card,
    .staff-card-link:focus .staff-card {
        transform: translateY(-6px);
        box-shadow: 0 15px 35px rgba(234, 88, 12, 0.2);
    }

    .staff-card-link:hover .staff-card::before,
    .staff-card-link:focus .staff-card::before {
        opacity: 1;
    }

    .staff-photo {
        width: 100%;
        height: 220px;
        object-fit: cover;
        background: #e2e8f0;
    }

    .staff-content {
        padding: 20px;
        display: flex;
        flex-direction: column;
        flex: 1;
    }

    .staff-name {
        font-size: 18px;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 6px;
    }

    .staff-designation {
        font-size: 14px;
        color: #636f7d;
        margin-bottom: auto;
    }

    .staff-detail-wrapper {
        background: #ffffff;
        border-radius: 20px;
        box-shadow: 0 12px 35px rgba(15, 23, 42, 0.12);
        overflow: hidden;
        margin-bottom: 30px;
    }

    .staff-detail-header {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 30px;
        padding: 30px;
        background: linear-gradient(135deg, rgba(249, 115, 22, 0.12), rgba(250, 204, 21, 0.16));
    }

    .staff-detail-photo {
        flex: 0 0 220px;
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 10px 25px rgba(234, 88, 12, 0.18);
    }

    .staff-detail-photo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .staff-detail-meta h3 {
        font-size: 28px;
        margin: 0 0 8px;
        color: #1e293b;
    }

    .staff-detail-meta .designation {
        font-size: 16px;
        color: #f97316;
        font-weight: 600;
        margin-bottom: 14px;
    }

    .staff-meta-list {
        list-style: none;
        padding: 0;
        margin: 0;
        display: grid;
        gap: 10px;
    }

    .staff-meta-list li {
        display: flex;
        gap: 10px;
        font-size: 14px;
        color: #475569;
    }

    .staff-meta-list span:first-child {
        font-weight: 600;
        color: #1e293b;
        width: 130px;
    }

    .staff-detail-body {
        padding: 30px;
        display: grid;
        gap: 32px;
    }

    .staff-section-title {
        font-size: 18px;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 16px;
    }

    .staff-note {
        background: #fff7ed;
        border-left: 4px solid #f97316;
        padding: 18px;
        border-radius: 10px;
        color: #78350f;
    }

    .staff-note p {
        margin: 0 0 8px;
    }

    .staff-note p:last-child {
        margin-bottom: 0;
    }

    .staff-table {
        width: 100%;
        border-collapse: collapse;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 6px 18px rgba(15, 23, 42, 0.08);
    }

    .staff-table thead {
        background: linear-gradient(135deg, #f97316, #facc15);
        color: #ffffff;
    }

    .staff-table th,
    .staff-table td {
        padding: 14px 16px;
        font-size: 14px;
        text-align: center;
        border-bottom: 1px solid rgba(251, 191, 36, 0.4);
    }

    .staff-table tbody tr:nth-child(even) {
        background: #fff7ed;
    }

    .staff-empty-state {
        padding: 40px;
        text-align: center;
        border: 2px dashed rgba(249, 115, 22, 0.3);
        border-radius: 18px;
        color: #92400e;
    }

    .back-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 18px;
        border-radius: 999px;
        border: 1px solid #fed7aa;
        color: #c2410c;
        text-decoration: none;
        font-weight: 500;
        margin-bottom: 20px;
        transition: background 0.3s ease, color 0.3s ease;
    }

    .back-link i {
        font-size: 13px;
    }

    .back-link:hover,
    .back-link:focus {
        background: #ffedd5;
        color: #9a3412;
    }

    @media (max-width: 992px) {
        .staff-directory {
            padding: 32px 20px;
        }

        .staff-grid {
            gap: 20px;
        }
    }

    @media (max-width: 768px) {
        .staff-detail-header {
            flex-direction: column;
            text-align: center;
            gap: 20px;
        }

        .staff-detail-photo {
            flex: 0 0 auto;
        }

        .staff-meta-list {
            text-align: left;
        }

        .staff-meta-list span:first-child {
            width: 110px;
        }
    }
</style>

<?php
$wpdb->hide_errors();

global $wpdb;

$page_url    = get_permalink();
$staff_id    = isset($_GET['staff']) ? absint($_GET['staff']) : 0;
$default_img = esc_url(get_template_directory_uri() . '/img/No_Image.jpg');
?>

<div class="b-title-page b-title-page_teacher b-title-page_6">
    <div class="container">
        <div class="row" style="min-height: 200px;background: #f5f5f5;">
            <div class="col-xs-12">
                <br><br>
                <div class="staff-directory__heading">
                    <h3><?php echo esc_html__('Meet Our Support Team', 's3schoolManagment'); ?></h3>
                    <p><?php echo esc_html__('Introducing the dedicated staff members who keep our institution running smoothly.', 's3schoolManagment'); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="b-layer-main">
    <div class="page-arrow">
        <i class="fa fa-angle-down" aria-hidden="true"></i>
    </div>
    <div class="b-blog-classic">
        <div class="container">
            <div class="row">
                <div class="col-xs-12 col-sm-7 col-md-8 col-lg-9">
                    <div class="b-blog-items-holder">
                        <div class="clearfix">
                            <?php if (!$staff_id) : ?>
                                <?php
                                $staff_members = $wpdb->get_results('SELECT staffid, staffName, staffImg, staffDesignation FROM ct_staff WHERE status="Present" ORDER BY staffName ASC');
                                ?>

                                <div class="staff-directory">
                                    <?php if (!empty($staff_members)) : ?>
                                        <div class="staff-grid">
                                            <?php foreach ($staff_members as $staff) :
                                                $photo_url = '';

                                                if (!empty($staff->staffImg)) {
                                                    $photo_url = esc_url($staff->staffImg);
                                                }

                                                if (empty($photo_url)) {
                                                    $photo_url = $default_img;
                                                }

                                                $profile_url = add_query_arg('staff', absint($staff->staffid), $page_url);
                                            ?>

                                                <a class="staff-card-link" href="<?php echo esc_url($profile_url); ?>">
                                                    <article class="staff-card">
                                                        <img class="staff-photo" src="<?php echo $photo_url; ?>" alt="<?php echo esc_attr($staff->staffName); ?>">
                                                        <div class="staff-content">
                                                            <h3 class="staff-name"><?php echo esc_html($staff->staffName); ?></h3>
                                                            <?php if (!empty($staff->staffDesignation)) : ?>
                                                                <p class="staff-designation"><?php echo esc_html($staff->staffDesignation); ?></p>
                                                            <?php endif; ?>
                                                        </div>
                                                    </article>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else : ?>
                                        <div class="staff-empty-state">
                                            <p><?php echo esc_html__('Staff profiles will appear here once they are added.', 's3schoolManagment'); ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>

                            <?php else : ?>
                                <?php
                                $staff = $wpdb->get_row($wpdb->prepare('SELECT * FROM ct_staff WHERE staffid = %d', $staff_id));
                                ?>

                                <?php if ($staff) : ?>
                                    <?php
                                    $photo_url = '';

                                    if (!empty($staff->staffImg)) {
                                        $photo_url = esc_url($staff->staffImg);
                                    }

                                    if (empty($photo_url)) {
                                        $photo_url = $default_img;
                                    }

                                    $formatted_birth   = (!empty($staff->staffBirth) && $staff->staffBirth !== '0000-00-00') ? mysql2date('d M Y', $staff->staffBirth) : '';
                                    $formatted_joining = (!empty($staff->staffJoining) && $staff->staffJoining !== '0000-00-00') ? mysql2date('d M Y', $staff->staffJoining) : '';

                                    $meta_rows = [
                                        __('Phone', 's3schoolManagment')        => $staff->staffPhone,
                                        __('Birth Date', 's3schoolManagment')    => $formatted_birth,
                                        __('Blood Group', 's3schoolManagment')   => $staff->staffBlood,
                                        __('Joining Date', 's3schoolManagment')  => $formatted_joining,
                                        __('Father', 's3schoolManagment')        => $staff->staffFather,
                                        __('Mother', 's3schoolManagment')        => $staff->staffMother,
                                        __('NID', 's3schoolManagment')           => $staff->staffNid,
                                        __('Index No', 's3schoolManagment')       => $staff->staffMpo,
                                    ];

                                    $qualifications = [];
                                    if (!empty($staff->staffQualificarion)) {
                                        $decoded = json_decode($staff->staffQualificarion, true);
                                        if (is_array($decoded)) {
                                            $qualifications = $decoded;
                                        }
                                    }

                                    $trainings = [];
                                    if (!empty($staff->staffTraining)) {
                                        $decoded = json_decode($staff->staffTraining, true);
                                        if (is_array($decoded)) {
                                            $trainings = $decoded;
                                        }
                                    }
                                    ?>

                                    <a class="back-link" href="<?php echo esc_url($page_url); ?>"><i class="fa fa-chevron-left" aria-hidden="true"></i><?php echo esc_html__('Back to all staff', 's3schoolManagment'); ?></a>

                                    <div class="staff-detail-wrapper">
                                        <div class="staff-detail-header">
                                            <div class="staff-detail-photo">
                                                <img src="<?php echo $photo_url; ?>" alt="<?php echo esc_attr($staff->staffName); ?>">
                                            </div>
                                            <div class="staff-detail-meta">
                                                <h3><?php echo esc_html($staff->staffName); ?></h3>
                                                <?php if (!empty($staff->staffDesignation)) : ?>
                                                    <div class="designation"><?php echo esc_html($staff->staffDesignation); ?></div>
                                                <?php endif; ?>

                                                <?php if (!empty(array_filter($meta_rows))) : ?>
                                                    <ul class="staff-meta-list">
                                                        <?php foreach ($meta_rows as $label => $value) : ?>
                                                            <?php if (!empty($value)) : ?>
                                                                <li><span><?php echo esc_html($label); ?>:</span><span><?php echo esc_html($value); ?></span></li>
                                                            <?php endif; ?>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div class="staff-detail-body">
                                            <?php if (!empty($staff->staffPresent) || !empty($staff->staffPermanent)) : ?>
                                                <div>
                                                    <h4 class="staff-section-title"><?php echo esc_html__('Address', 's3schoolManagment'); ?></h4>
                                                    <div class="staff-note">
                                                        <?php if (!empty($staff->staffPresent)) : ?>
                                                            <p><strong><?php echo esc_html__('Present:', 's3schoolManagment'); ?></strong> <?php echo esc_html($staff->staffPresent); ?></p>
                                                        <?php endif; ?>
                                                        <?php if (!empty($staff->staffPermanent)) : ?>
                                                            <p><strong><?php echo esc_html__('Permanent:', 's3schoolManagment'); ?></strong> <?php echo esc_html($staff->staffPermanent); ?></p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!empty($qualifications)) : ?>
                                                <div>
                                                    <h4 class="staff-section-title"><?php echo esc_html__('Educational Qualification', 's3schoolManagment'); ?></h4>
                                                    <div class="table-responsive">
                                                        <table class="staff-table">
                                                            <thead>
                                                                <tr>
                                                                    <th><?php echo esc_html__('SL', 's3schoolManagment'); ?></th>
                                                                    <th><?php echo esc_html__('Exam Name', 's3schoolManagment'); ?></th>
                                                                    <th><?php echo esc_html__('Div/Class', 's3schoolManagment'); ?></th>
                                                                    <th><?php echo esc_html__('Passing Year', 's3schoolManagment'); ?></th>
                                                                    <th><?php echo esc_html__('Board/University', 's3schoolManagment'); ?></th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php foreach ($qualifications as $index => $qualification) : ?>
                                                                    <?php if (is_array($qualification)) : ?>
                                                                        <tr>
                                                                            <td><?php echo esc_html($index + 1); ?></td>
                                                                            <?php foreach ($qualification as $cell_index => $cell_value) : ?>
                                                                                <?php
                                                                                $value = '';
                                                                                if (is_scalar($cell_value)) {
                                                                                    $value = ($cell_index === 1) ? '--' : (string) $cell_value;
                                                                                }
                                                                                ?>
                                                                                <td><?php echo esc_html($value); ?></td>
                                                                            <?php endforeach; ?>
                                                                        </tr>
                                                                    <?php endif; ?>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!empty($trainings)) : ?>
                                                <div>
                                                    <h4 class="staff-section-title"><?php echo esc_html__('Training Information', 's3schoolManagment'); ?></h4>
                                                    <div class="table-responsive">
                                                        <table class="staff-table">
                                                            <thead>
                                                                <tr>
                                                                    <th><?php echo esc_html__('SL', 's3schoolManagment'); ?></th>
                                                                    <th><?php echo esc_html__('Name', 's3schoolManagment'); ?></th>
                                                                    <th><?php echo esc_html__('Date', 's3schoolManagment'); ?></th>
                                                                    <th><?php echo esc_html__('Duration', 's3schoolManagment'); ?></th>
                                                                    <th><?php echo esc_html__('Organization', 's3schoolManagment'); ?></th>
                                                                    <th><?php echo esc_html__('Venue', 's3schoolManagment'); ?></th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php foreach ($trainings as $index => $training) : ?>
                                                                    <?php if (is_array($training)) : ?>
                                                                        <tr>
                                                                            <td><?php echo esc_html($index + 1); ?></td>
                                                                            <?php foreach ($training as $cell_index => $cell_value) : ?>
                                                                                <?php
                                                                                $value = '';
                                                                                if (is_scalar($cell_value)) {
                                                                                    $value = ($cell_index === 2) ? sprintf('%s %s', $cell_value, __('Days', 's3schoolManagment')) : (string) $cell_value;
                                                                                }
                                                                                ?>
                                                                                <td><?php echo esc_html($value); ?></td>
                                                                            <?php endforeach; ?>
                                                                        </tr>
                                                                    <?php endif; ?>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!empty($staff->staffNote)) : ?>
                                                <div>
                                                    <h4 class="staff-section-title"><?php echo esc_html__('Additional Notes', 's3schoolManagment'); ?></h4>
                                                    <div class="staff-note">
                                                        <?php echo wp_kses_post(wpautop($staff->staffNote)); ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php else : ?>
                                    <div class="staff-empty-state">
                                        <p><?php echo esc_html__('We couldn\'t find the profile you were looking for.', 's3schoolManagment'); ?></p>
                                        <a class="back-link" href="<?php echo esc_url($page_url); ?>"><i class="fa fa-chevron-left" aria-hidden="true"></i><?php echo esc_html__('Back to all staff', 's3schoolManagment'); ?></a>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-xs-12 col-sm-5 col-md-4 col-lg-3">
                    <?php get_sidebar(); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?>