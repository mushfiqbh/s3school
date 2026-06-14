<?php
/*
Template Name: Committee Page
*/

get_header();
?>

<style>
    .committee-directory {
        padding: 40px 30px;
        margin-bottom: 40px;
        background: #f8fafc;
        border-radius: 24px;
        box-shadow: inset 0 0 0 1px rgba(45, 212, 191, 0.15);
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

    .committee-grid {
        display: flex;
        flex-direction: column;
        gap: 24px;
    }

    .committee-card {
        height: 100%;
        background: #ffffff;
        border-radius: 18px;
        box-shadow: 0 10px 25px rgba(15, 118, 110, 0.12);
        overflow: hidden;
        position: relative;
        display: flex;
        flex-direction: row;
        gap: 0;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .committee-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 4px;
        background: linear-gradient(90deg, #14b8a6, #0ea5e9);
        opacity: 1;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .committee-card:hover,
    .committee-card:focus-within {
        transform: translateY(-6px);
        box-shadow: 0 16px 36px rgba(20, 184, 166, 0.25);
    }

    .committee-photo {
        flex: 0 0 220px;
        width: 220px;
        height: 100%;
        min-height: 220px;
        object-fit: cover;
        background: #e2e8f0;
    }

    .committee-content {
        padding: 24px;
        display: flex;
        flex-direction: column;
        flex: 1;
    }

    .committee-name {
        font-size: 18px;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 6px;
    }

    .committee-designation {
        font-size: 14px;
        color: #1f2937;
        margin-bottom: auto;
    }

    .committee-metadata {
        margin: 14px 0;
        padding: 12px;
        border-radius: 14px;
        background: rgba(240, 253, 250, 0.9);
        display: grid;
        gap: 8px;
        font-size: 13px;
        color: #0f172a;
    }

    .committee-metadata span {
        display: block;
    }

    .committee-metadata strong {
        display: block;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #0d9488;
        margin-bottom: 2px;
    }

    .committee-status-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-top: 14px;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        padding: 6px 12px;
        border-radius: 999px;
        background: rgba(20, 184, 166, 0.12);
        color: #0f766e;
        font-weight: 600;
    }

    .committee-status-badge.is-inactive {
        background: rgba(239, 68, 68, 0.12);
        color: #991b1b;
    }

    .committee-detail-wrapper {
        background: #ffffff;
        border-radius: 22px;
        box-shadow: 0 12px 35px rgba(15, 118, 110, 0.15);
        overflow: hidden;
        margin-bottom: 30px;
    }

    .committee-detail-header {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 30px;
        padding: 32px;
        background: linear-gradient(135deg, rgba(20, 184, 166, 0.16), rgba(14, 165, 233, 0.18));
    }

    .committee-detail-photo {
        flex: 0 0 220px;
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 12px 28px rgba(13, 148, 136, 0.2);
    }

    .committee-detail-photo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .committee-detail-meta h3 {
        font-size: 30px;
        margin: 0 0 10px;
        color: #0f172a;
    }

    .committee-detail-meta .designation {
        font-size: 17px;
        color: #0d9488;
        font-weight: 600;
        margin-bottom: 16px;
    }

    .committee-meta-list {
        list-style: none;
        padding: 0;
        margin: 0;
        display: grid;
        gap: 10px;
    }

    .committee-meta-list li {
        display: flex;
        gap: 10px;
        font-size: 14px;
        color: #1f2937;
    }

    .committee-meta-list span:first-child {
        font-weight: 600;
        color: #0f172a;
        width: 140px;
    }

    .committee-detail-body {
        padding: 32px;
        display: grid;
        gap: 28px;
    }

    .committee-section-title {
        font-size: 18px;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 16px;
    }

    .committee-note {
        background: #ecfeff;
        border-left: 4px solid #0ea5e9;
        padding: 18px;
        border-radius: 12px;
        color: #0f172a;
        line-height: 1.6;
    }

    .committee-note.card-note {
        margin-top: 16px;
        background: #f0f9ff;
        border-left-color: #0284c7;
    }

    .committee-empty-state {
        padding: 40px;
        text-align: center;
        border: 2px dashed rgba(13, 148, 136, 0.35);
        border-radius: 18px;
        color: #0f172a;
    }

    .committee-session-title {
        font-size: 24px;
        font-weight: 700;
        color: #0f172a;
        margin: 40px 0 20px;
        text-align: center;
    }

    .back-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 18px;
        border-radius: 999px;
        border: 1px solid rgba(14, 165, 233, 0.35);
        color: #0ea5e9;
        text-decoration: none;
        font-weight: 500;
        margin-bottom: 24px;
        transition: background 0.3s ease, color 0.3s ease;
    }

    .back-link i {
        font-size: 13px;
    }

    .back-link:hover,
    .back-link:focus {
        background: rgba(14, 165, 233, 0.1);
        color: #0284c7;
    }

    @media (max-width: 768px) {
        .committee-card {
            flex-direction: column;
        }

        .committee-photo {
            width: 100%;
            min-height: 200px;
        }

        .committee-content {
            padding: 20px;
        }

        .committee-detail-header {
            flex-direction: column;
            text-align: center;
            gap: 22px;
        }

        .committee-meta-list {
            text-align: left;
        }

        .committee-meta-list span:first-child {
            width: 120px;
        }
    }
</style>

<?php
$wpdb->hide_errors();

global $wpdb;

$default_img   = esc_url(get_template_directory_uri() . '/img/No_Image.jpg');

$badge_for_status = static function ($status) {
    $status = strtolower((string) $status);
    $classes = 'committee-status-badge';
    if ($status !== 'active') {
        $classes .= ' is-inactive';
    }
    return $classes;
};
?>

<div class="b-title-page b-title-page_teacher b-title-page_6">
    <div class="container">
        <div class="row" style="min-height: 200px;background: #f5f9ff;">
            <div class="col-xs-12">
                <br><br>
                <div class="committee-directory__heading">
                    <h3><?php echo esc_html__('Meet Our Governing Body', 's3schoolManagment'); ?></h3>
                    <p><?php echo esc_html__('Introducing the members guiding our institution with vision and integrity.', 's3schoolManagment'); ?></p>
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
                <div class="col-xs-12">
                    <div class="b-blog-items-holder">
                        <div class="clearfix">
                            <?php
                            $committees = $wpdb->get_results("SELECT * FROM ct_committee WHERE committeeStatus IN ('active','inactive') ORDER BY committeeSession DESC, committeeName ASC");

                            $present = [];
                            $former = [];
                            foreach ($committees as $member) {
                                if (strtolower($member->committeeStatus) === 'active') {
                                    $present[] = $member;
                                } else {
                                    $session = $member->committeeSession ?: 'Unknown';
                                    $former[$session][] = $member;
                                }
                            }
                            ?>

                            <div class="committee-directory">
                                <?php if (!empty($present) || !empty($former)) : 
                                    if (!empty($present)) :
                                ?>
                                    <h3 class="committee-session-title"><?php echo esc_html__('Present Committee', 's3schoolManagment'); ?></h3>
                                    <div class="committee-grid">
                                        <?php foreach ($present as $member) :
                                            $photo_url = '';

                                            if (!empty($member->committeeImg)) {
                                                $raw = trim($member->committeeImg);
                                                if (strpos($raw, 'http') === 0) {
                                                    $photo_url = esc_url($raw);
                                                } else {
                                                    $photo_url = esc_url(home_url('/') . ltrim($raw, '/'));
                                                }
                                            }

                                            if (empty($photo_url)) {
                                                $photo_url = $default_img;
                                            }

                                            $status_label = !empty($member->committeeStatus) ? esc_html(ucfirst($member->committeeStatus)) : '';
                                        ?>

                                            <article class="committee-card">
                                                <img class="committee-photo" src="<?php echo $photo_url; ?>" alt="<?php echo esc_attr($member->committeeName); ?>">
                                                <div class="committee-content">
                                                    <h3 class="committee-name"><?php echo esc_html($member->committeeName); ?></h3>
                                                    <?php if (!empty($member->committeeDesignation)) : ?>
                                                        <p class="committee-designation"><?php echo esc_html($member->committeeDesignation); ?></p>
                                                    <?php endif; ?>

                                                    <?php
                                                    $has_meta = !empty($member->committeeFather) || !empty($member->committeeMother) || !empty($member->committeeSession);
                                                    if ($has_meta) :
                                                    ?>
                                                        <div class="committee-metadata">
                                                            <?php if (!empty($member->committeeFather)) : ?>
                                                                <span><strong><?php echo esc_html__('Father', 's3schoolManagment'); ?></strong><?php echo esc_html($member->committeeFather); ?></span>
                                                            <?php endif; ?>
                                                            <?php if (!empty($member->committeeMother)) : ?>
                                                                <span><strong><?php echo esc_html__('Mother', 's3schoolManagment'); ?></strong><?php echo esc_html($member->committeeMother); ?></span>
                                                            <?php endif; ?>
                                                            <?php if (!empty($member->committeeSession)) : ?>
                                                                <span><strong><?php echo esc_html__('Session', 's3schoolManagment'); ?></strong><?php echo esc_html($member->committeeSession); ?></span>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endif; ?>

                                                    <?php if (!empty($status_label)) : ?>
                                                        <span class="<?php echo esc_attr($badge_for_status($member->committeeStatus)); ?>">
                                                            <i class="fa fa-user-circle" aria-hidden="true"></i><?php echo $status_label; ?>
                                                        </span>
                                                    <?php endif; ?>

                                                    <?php if (!empty($member->committeeNote)) : ?>
                                                        <div class="committee-note card-note"><?php echo wp_kses_post(nl2br($member->committeeNote)); ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </article>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($former)) :
                                    foreach ($former as $session => $members) :
                                ?>
                                    <h3 class="committee-session-title"><?php echo esc_html__('Former Committee', 's3schoolManagment') . ' - ' . esc_html($session); ?></h3>
                                    <div class="committee-grid">
                                        <?php foreach ($members as $member) :
                                            $photo_url = '';

                                            if (!empty($member->committeeImg)) {
                                                $raw = trim($member->committeeImg);
                                                if (strpos($raw, 'http') === 0) {
                                                    $photo_url = esc_url($raw);
                                                } else {
                                                    $photo_url = esc_url(home_url('/') . ltrim($raw, '/'));
                                                }
                                            }

                                            if (empty($photo_url)) {
                                                $photo_url = $default_img;
                                            }

                                            $status_label = !empty($member->committeeStatus) ? esc_html(ucfirst($member->committeeStatus)) : '';
                                        ?>

                                            <article class="committee-card">
                                                <img class="committee-photo" src="<?php echo $photo_url; ?>" alt="<?php echo esc_attr($member->committeeName); ?>">
                                                <div class="committee-content">
                                                    <h3 class="committee-name"><?php echo esc_html($member->committeeName); ?></h3>
                                                    <?php if (!empty($member->committeeDesignation)) : ?>
                                                        <p class="committee-designation"><?php echo esc_html($member->committeeDesignation); ?></p>
                                                    <?php endif; ?>

                                                    <?php
                                                    $has_meta = !empty($member->committeeFather) || !empty($member->committeeMother) || !empty($member->committeeSession);
                                                    if ($has_meta) :
                                                    ?>
                                                        <div class="committee-metadata">
                                                            <?php if (!empty($member->committeeFather)) : ?>
                                                                <span><strong><?php echo esc_html__('Father', 's3schoolManagment'); ?></strong><?php echo esc_html($member->committeeFather); ?></span>
                                                            <?php endif; ?>
                                                            <?php if (!empty($member->committeeMother)) : ?>
                                                                <span><strong><?php echo esc_html__('Mother', 's3schoolManagment'); ?></strong><?php echo esc_html($member->committeeMother); ?></span>
                                                            <?php endif; ?>
                                                            <?php if (!empty($member->committeeSession)) : ?>
                                                                <span><strong><?php echo esc_html__('Session', 's3schoolManagment'); ?></strong><?php echo esc_html($member->committeeSession); ?></span>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endif; ?>

                                                    <?php if (!empty($status_label)) : ?>
                                                        <span class="<?php echo esc_attr($badge_for_status($member->committeeStatus)); ?>">
                                                            <i class="fa fa-user-circle" aria-hidden="true"></i><?php echo $status_label; ?>
                                                        </span>
                                                    <?php endif; ?>

                                                    <?php if (!empty($member->committeeNote)) : ?>
                                                        <div class="committee-note card-note"><?php echo wp_kses_post(nl2br($member->committeeNote)); ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </article>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endforeach; ?>
                                <?php endif; ?>
                                <?php else : ?>
                                    <div class="committee-empty-state">
                                        <p><?php echo esc_html__('Committee profiles will appear here once they are added.', 's3schoolManagment'); ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
get_footer();
?>