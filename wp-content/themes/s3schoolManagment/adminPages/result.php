<?php

/**
 * Template Name: Admin Result
 */
global $wpdb;

$haveAccess = false;
if (isset(wp_get_current_user()->roles[0]) || current_user_can('administrator')) {
    if (wp_get_current_user()->roles[0] == 'um_headmaster' || current_user_can('administrator')) {
        $haveAccess = true;
    }
}

/*=================
	Add Result
=================*/

if (isset($_POST['stdids'])) {


    $resExam            = $_POST['resExam'];
    $resSubject         = $_POST['resSubject'];
    $resultYear         = $_POST['resultYear'];
    $resSubPaper        = $_POST['resSubPaper'];
    $resCombineWith = $_POST['resCombineWith'];
    $combine = $_POST['combineMark'];

    $subCQ         = $_POST['subCQ'];
    $subMCQ     = $_POST['subMCQ'];
    $subPect     = $_POST['subPect'];


    foreach ($_POST['stdids'] as $student) {

        if ((isset($_POST['cq'][$student]) && $_POST['cq'][$student] != "") || (isset($_POST['mcq'][$student]) && $_POST['mcq'][$student] != "")  || (isset($_POST['ca'][$student]) && $_POST['ca'][$student] != "")) {
            $restota = 0;
            $stdCQ   = (is_numeric($_POST['cq'][$student]) & $_POST['cq'][$student] != '')    ? $_POST['cq'][$student] : 0;
            $stdMCQ  = (is_numeric($_POST['mcq'][$student]) & $_POST['mcq'][$student] != '')    ? $_POST['mcq'][$student] : 0;
            $stdPrec = (is_numeric($_POST['prac'][$student]) & $_POST['prac'][$student] != '')    ? $_POST['prac'][$student] : 0;
            $stdCa = (is_numeric($_POST['ca'][$student]) & $_POST['ca'][$student] != '')    ? $_POST['ca'][$student] : 0;

            $restota += $stdCQ + $stdMCQ + $stdPrec + $stdCa;

            $insert = $wpdb->insert(
                'ct_result',
                array(
                    'resStudentId'         => $student,
                    'resClass'              => $_POST['resclass'],
                    'resSubPaper'          => $_POST['resSubPaper'],
                    'resgroup'              => $_POST['group'][$student],
                    'resSec'                  => $_POST['section'][$student],
                    'resExam'                  => $_POST['resExam'],
                    'resSubject'          => $_POST['resSubject'],
                    'resultYear'          => $_POST['resultYear'],
                    'resCombineWith'     => $_POST['resCombineWith'],
                    'resSubOpt'             => $_POST['optional'][$student],
                    'resSub4th'             => $_POST['sub4th'][$student],
                    'resStdRoll'             => $_POST['roll'][$student],
                    'resCQ'                     => $_POST['cq'][$student],
                    'resMCQ'                     => $_POST['mcq'][$student],
                    'resPrec'                 => $_POST['prac'][$student],
                    'resCa'                 => $_POST['ca'][$student],
                    'resTotal'                 => $restota,
                    'resAdd'                    => get_current_user_id()
                )
            );
        }
    }


    if (isset($insert)) {
        $message = array('status' => 'success', 'message' => 'Successfully Added');
    } else {
        $message = array('status' => 'faild', 'message' => 'Something wrong, Make sure you fill correct input.');
    }
}



/*Update Result*/
if (isset($_POST['updateRes'])) {

    $update = $wpdb->update(
        'ct_result',
        array(
            'resCQ'         => $_POST['CQ'],
            'resMCQ'         => $_POST['MCQ'],
            'resPrec'     => $_POST['P'],
            'resCa'     => $_POST['ca'],
            'resTotal'     => isnum($_POST['CQ']) + isnum($_POST['MCQ']) + isnum($_POST['P']) + isnum($_POST['ca'])
        ),
        array('resultId' => $_POST['id'])
    );

    if ($update) {
        $message = array('status' => 'success', 'message' => 'Successfully updated');
    } else {
        $message = array('status' => 'faild', 'message' => 'Something wrong please try again');
    }
}

/*Delete Result*/
if (isset($_POST['deleteResult'])) {
    $id = $_POST['id'];
    $wpdb->delete(
        'ct_result',
        array(
            'resultId' => $id
        )
    );
}


?>

<?php if (! is_admin()) {
    get_header(); ?>
    <div class="b-layer-main">

        <div class="">
            <div class="container">
                <div class="row">
                    <div class="col-md-12">

                    <?php } ?>

                    <p id="theSiteURL" class="hidden"><?= get_template_directory_uri() ?></p>

                    <div class="container-fluid maxAdminpages" style="padding-left: 0">

                        <?php

                        if (isset($message)) {
                        ?>
                            <div class="messageDiv">
                                <div class="alert <?= ($message['status'] == 'success') ? 'alert-success' : 'alert-danger';  ?>">
                                    <?= $message['message'] ?>
                                </div>
                            </div>
                        <?php
                        }
                        ?>


                        <h2 class="resmangh2">Result Management
                            <a href="?page=result&view=marksheet" class="btn btn-primary pull-right">Blank Mark Sheet</a>
                            <?php if ($haveAccess) { ?>
                                <a href="?page=result&view=delete" class="btn btn-primary pull-right">Detele</a>
                                <a href="?page=result&view=allresult" class="btn btn-primary pull-right">All Result</a>
                            <?php } ?>
                            <a href="?page=result&view=resultedit" class="btn btn-primary pull-right">Edit</a>
                            <a href="?page=result" class="btn btn-primary pull-right">Add</a>
                        </h2><br>


                        <?php


                        if (!isset($_GET['view'])) {
                            require 'inc/result-add.php';
                        } elseif ($_GET['view'] == 'result') { //Single Result View
                            require 'inc/result-result.php';
                        } elseif ($_GET['view'] == 'allresult') {
                            require 'inc/result-allresult.php';
                        } elseif ($_GET['view'] == 'edit') { //Single Result Edit
                            require 'inc/result-edit.php';
                        } elseif ($_GET['view'] == 'resultedit') { //All Result Edit
                            require 'inc/allresult-edit.php';
                        } elseif ($_GET['view'] == 'delete') {
                            require 'inc/result-delete.php';
                        } elseif ($_GET['view'] == 'marksheet') {
                            require 'inc/blank-marksheet.php';
                        }

                        ?>

                    </div>

                    <?php if (! is_admin()) { ?>

                    </div>
                </div>
            </div>
        </div>
    </div>
<?php get_footer();
                    } ?>

<script type="text/javascript">
    (function($) {
        $('#resultClass').change(function() {
            var $siteUrl = $('#theSiteURL').text();
            $.ajax({
                url: $siteUrl + "/inc/ajaxAction.php",
                method: "POST",
                data: {
                    class: $(this).val(),
                    type: 'getExams'
                },
                dataType: "html"
            }).done(function(msg) {
                $("#resultExam").html(msg);
                $("#resultExam").prop('disabled', false);
            });

            $.ajax({
                url: $siteUrl + "/inc/ajaxAction.php",
                method: "POST",
                data: {
                    class: $(this).val(),
                    type: 'getYears'
                },
                dataType: "html"
            }).done(function(msg) {
                $("#resultYear").html(msg);
                $("#resultYear").prop('disabled', false);
            });

            $.ajax({
                url: $siteUrl + "/inc/ajaxAction.php",
                method: "POST",
                data: {
                    class: $(this).val(),
                    type: 'getSection'
                },
                dataType: "html"
            }).done(function(msg) {
                $("#resultSection").html(msg);
                $("#resultSection").prop('disabled', false);
            });
        });


        $('#resultExam').change(function() {
            var $siteUrl = $('#theSiteURL').text();

            $.ajax({
                url: $siteUrl + "/inc/ajaxAction.php",
                method: "POST",
                data: {
                    exam: $(this).val(),
                    type: 'getExamSubject'
                },
                dataType: "html"
            }).done(function(msg) {
                $("#resultSubject").html(msg);
                $("#resultSubject").prop('disabled', false);
            });

        });

        $('.resultInput').keyup(function(event) {
            $this = $(this);
            $val = $this.val();
            $max = $this.data('max');

            if ($val == '' || $val < ($max + 1) || $val == 'A' || $val == 'a') {
                $this.css('border-color', '#ddd');
                $this.removeClass('haserror');
            } else {
                $this.addClass('haserror');
                $this.css('border-color', 'red');
                $('.resultSubmit').prop('disabled', true);
            }

            if ($('.resultInput.haserror').length == 0) {
                $('.resultSubmit').prop('disabled', false);
            }

        });
    })(jQuery);
</script>