<?php
/*
Template Name: Governing Body Page
*/

get_header();
?>

<style>
    .governing-body-directory {
        padding: 40px 30px;
        margin-bottom: 40px;
        background: #f8fafc;
        border-radius: 24px;
        box-shadow: inset 0 0 0 1px rgba(45, 212, 191, 0.15);
    }

    .governing-body-directory__heading {
        text-align: center;
        max-width: 560px;
        margin: 0 auto 35px;
    }

    .governing-body-directory__heading h3 {
        font-size: 28px;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 12px;
    }

    .governing-body-directory__heading p {
        margin: 0;
        color: #475569;
        font-size: 15px;
        line-height: 1.6;
    }

    .governing-body-grid {
        display: flex;
        flex-direction: column;
        gap: 24px;
    }

    .governing-body-card {
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

    .governing-body-card::before {
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

    .governing-body-card:hover,
    .governing-body-card:focus-within {
        transform: translateY(-6px);
        box-shadow: 0 16px 36px rgba(20, 184, 166, 0.25);
    }

    .governing-body-photo {
        flex: 0 0 220px;
        width: 220px;
        height: 100%;
        min-height: 220px;
        object-fit: cover;
        background: #e2e8f0;
    }

    .governing-body-content {
        padding: 24px;
        display: flex;
        flex-direction: column;
        flex: 1;
    }

    .governing-body-name {
        font-size: 18px;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 6px;
    }

    .governing-body-designation {
        font-size: 14px;
        color: #1f2937;
        margin-bottom: auto;
    }

    .governing-body-metadata {
        margin: 14px 0;
        padding: 12px;
        border-radius: 14px;
        background: rgba(240, 253, 250, 0.9);
        display: grid;
        gap: 8px;
        font-size: 13px;
        color: #0f172a;
    }

    .governing-body-metadata span {
        display: block;
    }

    .governing-body-metadata strong {
        display: block;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #0d9488;
        margin-bottom: 2px;
    }

    .governing-body-status-badge {
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

    .governing-body-status-badge.is-inactive {
        background: rgba(239, 68, 68, 0.12);
        color: #991b1b;
    }

    .governing-body-detail-wrapper {
        background: #ffffff;
        border-radius: 22px;
        box-shadow: 0 12px 35px rgba(15, 118, 110, 0.15);
        overflow: hidden;
        margin-bottom: 30px;
    }

    .governing-body-detail-header {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 30px;
        padding: 32px;
        background: linear-gradient(135deg, rgba(20, 184, 166, 0.16), rgba(14, 165, 233, 0.18));
    }

    .governing-body-detail-photo {
        flex: 0 0 220px;
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 12px 28px rgba(13, 148, 136, 0.2);
    }

    .governing-body-detail-photo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .governing-body-detail-meta h3 {
        font-size: 30px;
        margin: 0 0 10px;
        color: #0f172a;
    }

    .governing-body-detail-meta .designation {
        font-size: 17px;
        color: #0d9488;
        font-weight: 600;
        margin-bottom: 16px;
    }

    .governing-body-meta-list {
        list-style: none;
        padding: 0;
        margin: 0;
        display: grid;
        gap: 10px;
    }

    .governing-body-meta-list li {
        display: flex;
        gap: 10px;
        font-size: 14px;
        color: #1f2937;
    }

    .governing-body-meta-list span:first-child {
        font-weight: 600;
        color: #0f172a;
        width: 140px;
    }

    .governing-body-detail-body {
        padding: 32px;
        display: grid;
        gap: 28px;
    }

    .governing-body-section-title {
        font-size: 18px;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 16px;
    }

    .governing-body-note {
        background: #ecfeff;
        border-left: 4px solid #0ea5e9;
        padding: 18px;
        border-radius: 12px;
        color: #0f172a;
        line-height: 1.6;
    }

    .governing-body-note.card-note {
        margin-top: 16px;
        background: #f0f9ff;
        border-left-color: #0284c7;
    }

    .governing-body-empty-state {
        padding: 40px;
        text-align: center;
        border: 2px dashed rgba(13, 148, 136, 0.35);
        border-radius: 18px;
        color: #0f172a;
    }

    .governing-body-session-title {
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
        .governing-body-card {
            flex-direction: column;
        }

        .governing-body-photo {
            width: 100%;
            min-height: 200px;
        }

        .governing-body-content {
            padding: 20px;
        }

        .governing-body-detail-header {
            flex-direction: column;
            text-align: center;
            gap: 22px;
        }

        .governing-body-meta-list {
            text-align: left;
        }

        .governing-body-meta-list span:first-child {
            width: 120px;
        }
    }
</style>

<?php
$wpdb->hide_errors();

global $wpdb;

$default_img = esc_url(get_template_directory_uri() . '/img/No_Image.jpg');

$badge_for_status = static function ($is_active) {
    $classes = 'governing-body-status-badge';
    if ((int) $is_active !== 1) {
        $classes .= ' is-inactive';
    }
    return $classes;
};

$governing_bodies = $wpdb->get_results(
    "SELECT * FROM ct_governing_body ORDER BY is_active DESC, governing_body_session DESC, order_number ASC, governing_body_name ASC"
);

$current_members = [];
$former_members = [];

if (!empty($governing_bodies)) {
    foreach ($governing_bodies as $member) {
        if ((int) $member->is_active === 1) {
            $current_members[] = $member;
        } else {
            $session = !empty($member->governing_body_session) ? $member->governing_body_session : __('Unknown', 's3schoolManagment');
            $former_members[$session][] = $member;
        }
    }
}
?>

<div class="b-title-page b-title-page_teacher b-title-page_6">
    <div class="container">
        <div class="row" style="min-height: 200px;background: #f5f9ff;">
            <div class="col-xs-12">
                <br><br>
                <div class="governing-body-directory__heading">
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
                            <div class="governing-body-directory">
                                <?php if (!empty($current_members) || !empty($former_members)) :
                                    if (!empty($current_members)) :
                                ?>
                                    <h3 class="governing-body-session-title"><?php echo esc_html__('Current Governing Body', 's3schoolManagment'); ?></h3>
                                    <div class="governing-body-grid">
                                        <?php foreach ($current_members as $member) :
                                            $photo_url = '';

                                            if (!empty($member->governing_body_image)) {
                                                $raw = trim($member->governing_body_image);
                                                if (strpos($raw, 'http') === 0) {
                                                    $photo_url = esc_url($raw);
                                                } else {
                                                    $photo_url = esc_url(home_url('/') . ltrim($raw, '/'));
                                                }
                                            }

                                            if (empty($photo_url)) {
                                                $photo_url = $default_img;
                                            }

                                            $status_label = (int) $member->is_active === 1 ? esc_html__('Active', 's3schoolManagment') : esc_html__('Inactive', 's3schoolManagment');
                                        ?>

                                            <article class="governing-body-card">
                                                <img class="governing-body-photo" src="<?php echo $photo_url; ?>" alt="<?php echo esc_attr($member->governing_body_name); ?>">
                                                <div class="governing-body-content">
                                                    <h3 class="governing-body-name"><?php echo esc_html($member->governing_body_name); ?></h3>
                                                    <?php if (!empty($member->governing_body_designation)) : ?>
                                                        <p class="governing-body-designation"><?php echo esc_html($member->governing_body_designation); ?></p>
                                                    <?php endif; ?>

                                                    <?php
                                                    $has_meta = !empty($member->governing_body_father_name) || !empty($member->governing_body_mother_name) || !empty($member->governing_body_session);
                                                    if ($has_meta) :
                                                    ?>
                                                        <div class="governing-body-metadata">
                                                            <?php if (!empty($member->governing_body_father_name)) : ?>
                                                                <span><strong><?php echo esc_html__('Father', 's3schoolManagment'); ?></strong><?php echo esc_html($member->governing_body_father_name); ?></span>
                                                            <?php endif; ?>
                                                            <?php if (!empty($member->governing_body_mother_name)) : ?>
                                                                <span><strong><?php echo esc_html__('Mother', 's3schoolManagment'); ?></strong><?php echo esc_html($member->governing_body_mother_name); ?></span>
                                                            <?php endif; ?>
                                                            <?php if (!empty($member->governing_body_session)) : ?>
                                                                <span><strong><?php echo esc_html__('Session', 's3schoolManagment'); ?></strong><?php echo esc_html($member->governing_body_session); ?></span>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endif; ?>

                                                    <?php if (!empty($status_label)) : ?>
                                                        <span class="<?php echo esc_attr($badge_for_status($member->is_active)); ?>">
                                                            <i class="fa fa-user-circle" aria-hidden="true"></i><?php echo $status_label; ?>
                                                        </span>
                                                    <?php endif; ?>

                                                    <?php if (!empty($member->note)) : ?>
                                                        <div class="governing-body-note card-note"><?php echo wp_kses_post(nl2br($member->note)); ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </article>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($former_members)) :
                                    foreach ($former_members as $session => $members) :
                                ?>
                                    <h3 class="governing-body-session-title"><?php echo esc_html__('Former Governing Body', 's3schoolManagment') . ' - ' . esc_html($session); ?></h3>
                                    <div class="governing-body-grid">
                                        <?php foreach ($members as $member) :
                                            $photo_url = '';

                                            if (!empty($member->governing_body_image)) {
                                                $raw = trim($member->governing_body_image);
                                                if (strpos($raw, 'http') === 0) {
                                                    $photo_url = esc_url($raw);
                                                } else {
                                                    $photo_url = esc_url(home_url('/') . ltrim($raw, '/'));
                                                }
                                            }

                                            if (empty($photo_url)) {
                                                $photo_url = $default_img;
                                            }

                                            $status_label = (int) $member->is_active === 1 ? esc_html__('Active', 's3schoolManagment') : esc_html__('Inactive', 's3schoolManagment');
                                        ?>

                                            <article class="governing-body-card">
                                                <img class="governing-body-photo" src="<?php echo $photo_url; ?>" alt="<?php echo esc_attr($member->governing_body_name); ?>">
                                                <div class="governing-body-content">
                                                    <h3 class="governing-body-name"><?php echo esc_html($member->governing_body_name); ?></h3>
                                                    <?php if (!empty($member->governing_body_designation)) : ?>
                                                        <p class="governing-body-designation"><?php echo esc_html($member->governing_body_designation); ?></p>
                                                    <?php endif; ?>

                                                    <?php
                                                    $has_meta = !empty($member->governing_body_father_name) || !empty($member->governing_body_mother_name) || !empty($member->governing_body_session);
                                                    if ($has_meta) :
                                                    ?>
                                                        <div class="governing-body-metadata">
                                                            <?php if (!empty($member->governing_body_father_name)) : ?>
                                                                <span><strong><?php echo esc_html__('Father', 's3schoolManagment'); ?></strong><?php echo esc_html($member->governing_body_father_name); ?></span>
                                                            <?php endif; ?>
                                                            <?php if (!empty($member->governing_body_mother_name)) : ?>
                                                                <span><strong><?php echo esc_html__('Mother', 's3schoolManagment'); ?></strong><?php echo esc_html($member->governing_body_mother_name); ?></span>
                                                            <?php endif; ?>
                                                            <?php if (!empty($member->governing_body_session)) : ?>
                                                                <span><strong><?php echo esc_html__('Session', 's3schoolManagment'); ?></strong><?php echo esc_html($member->governing_body_session); ?></span>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endif; ?>

                                                    <?php if (!empty($status_label)) : ?>
                                                        <span class="<?php echo esc_attr($badge_for_status($member->is_active)); ?>">
                                                            <i class="fa fa-user-circle" aria-hidden="true"></i><?php echo $status_label; ?>
                                                        </span>
                                                    <?php endif; ?>

                                                    <?php if (!empty($member->note)) : ?>
                                                        <div class="governing-body-note card-note"><?php echo wp_kses_post(nl2br($member->note)); ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </article>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endforeach; ?>
                                <?php endif; ?>
                                <?php else : ?>
                                    <div class="governing-body-empty-state">
                                        <p><?php echo esc_html__('Governing body profiles will appear here once they are added.', 's3schoolManagment'); ?></p>
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