<?php
/*
Template Name: Committee Page
*/

get_header();
?>

<style>
    body {
        font-family: Arial, sans-serif;
        background: #f2f6f7;
        padding: 20px;
    }

    .committee-directory__heading {
        text-align: center;
        max-width: 560px;
        margin: 0 auto 35px;
    }

    .committee-directory__heading h3 {
        font-size: 28px;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 12px;
    }

    .committee-directory__heading p {
        margin: 0;
        color: #475569;
        font-size: 15px;
        line-height: 1.6;
    }

    .table-container {
        overflow-x: auto;
        background: #ffffff;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        margin-bottom: 30px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        background: white;
    }

    th, td {
        border: 1px solid #ccc;
        padding: 10px 12px;
        vertical-align: top;
        text-align: left;
        font-size: 15px;
    }

    th {
        background: #e8eef1;
        font-weight: bold;
    }

    .committee-title {
        font-weight: bold;
        width: 180px;
    }

    .job-desc {
        width: 260px;
    }

    .member-label {
        font-weight: bold;
        color: #0d9488;
        margin-right: 5px;
    }

    .committee-empty-state {
        padding: 40px;
        text-align: center;
        border: 2px dashed rgba(13, 148, 136, 0.35);
        border-radius: 18px;
        color: #0f172a;
        background: #ffffff;
        margin: 20px 0;
    }

    @media (max-width: 768px) {
        .table-container {
            padding: 10px;
        }

        th, td {
            padding: 8px;
            font-size: 14px;
        }

        .committee-title {
            width: 120px;
        }

        .job-desc {
            width: 180px;
        }
    }
</style>

<?php
$wpdb->hide_errors();
global $wpdb;
?>

<div class="b-title-page b-title-page_teacher b-title-page_6">
    <div class="container">
        <div class="row" style="min-height: 200px;background: #f5f9ff;">
            <div class="col-xs-12">
                <br><br>
                <div class="committee-directory__heading">
                    <h3><?php echo esc_html__('Meet Our Committees', 's3schoolManagment'); ?></h3>
                    <p><?php echo esc_html__('Explore the dedicated committees working together for institutional excellence and student success.', 's3schoolManagment'); ?></p>
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

        <?php
        // Fetch all active committees with their members
        $committees = $wpdb->get_results("
            SELECT 
                c.committee_id,
                c.committee_title,
                c.job_description,
                c.is_active
            FROM ct_committees c
            WHERE c.is_active = 1
            ORDER BY c.sort_order, c.committee_title
        ");

        if (!empty($committees)) :
            $committee_counter = 1;
            foreach ($committees as $committee) :
                // Fetch members for this committee
                $members = $wpdb->get_results($wpdb->prepare("
                    SELECT 
                        member_label,
                        member_name,
                        member_designation,
                        member_subject,
                        member_position
                    FROM ct_committee_members
                    WHERE committee_id = %d AND is_active = 1
                    ORDER BY sort_order, member_name
                ", $committee->committee_id));

                if (!empty($members)) :
                    $member_count = count($members);
        ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Name of Committee</th>
                                    <th>Name, Designation and Subject</th>
                                    <th>Position</th>
                                    <th>Job Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $first_member = true;
                                foreach ($members as $member) :
                                ?>
                                    <tr>
                                        <?php if ($first_member) : ?>
                                            <td rowspan="<?php echo $member_count; ?>" class="committee-title">
                                                <?php echo str_pad($committee_counter, 2, '0', STR_PAD_LEFT); ?>. <?php echo esc_html($committee->committee_title); ?>
                                            </td>
                                        <?php endif; ?>
                                        
                                        <td>
                                            <?php if (!empty($member->member_label)) : ?>
                                                (<?php echo esc_html($member->member_label); ?>)
                                            <?php endif; ?>
                                            <?php echo esc_html($member->member_name); ?><?php 
                                            if (!empty($member->member_designation)) {
                                                echo ', ' . esc_html($member->member_designation);
                                            }
                                            if (!empty($member->member_subject)) {
                                                echo ', ' . esc_html($member->member_subject);
                                            }
                                            ?>
                                        </td>
                                        
                                        <td><?php echo !empty($member->member_position) ? esc_html($member->member_position) : '—'; ?></td>
                                        
                                        <?php if ($first_member) : ?>
                                            <td rowspan="<?php echo $member_count; ?>" class="job-desc">
                                                <?php echo !empty($committee->job_description) ? wp_kses_post(nl2br($committee->job_description)) : '—'; ?>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php
                                    $first_member = false;
                                endforeach;
                                ?>
                            </tbody>
                        </table>
                    </div>
        <?php
                    $committee_counter++;
                endif;
            endforeach;
        else :
        ?>
            <div class="committee-empty-state">
                <p><?php echo esc_html__('No committees available at this time. Please check back later.', 's3schoolManagment'); ?></p>
            </div>
        <?php endif; ?>
        </div>
    </div>
</div>

<?php
get_footer();
?>