(function($) {
  $(document).ready(function() {

    "use strict";
    
    
    /*====================
    == SMS Page ==
    =====================*/
    $('#selectAll').change(function(event) {
  
      if ($(this).is(":checked")) {
        $(this).closest('table').find('.stdSel').prop( "checked", true );
      }else{
        $(this).closest('table').find('.stdSel').prop( "checked", false );
      }
    });


    /*Sms Count*/
    $(".smsCount").keyup(function(event) {
      var len = $(this).val().length;
      var total = 1;
      $(this).closest('.form-group').find('.ramain').text(len);

      var remain = 750-len;
      if(remain < 0)
        remain = 0;

      $(this).closest('.form-group').removeClass('has-error');
      if(len > 750){
        $(this).closest('.form-group').addClass('has-error');
      }

      if(len > 150){ total = ((len-1)/150)+1; }
      $(this).closest('.form-group').find('.totalSms').text(parseInt(total));
      $(this).closest('.form-group').find('.left').text(parseInt(remain));

    });


    
    /*====================
    == Id Card Page ==
    =====================*/
    $('#idAllStudent').change(function() {
      if($(this).is(":checked")) {
        $('#idRoll').hide('fast');
      }else{
        $('#idRoll').show('fast');
      }
    });


  
    /*====================
    == Roll Checker ==
    =====================*/
    $('#stdRoll').on("focusout",function(e){
      var $this = $(this);
      var $std = $this.data('std');
      var $warning = $this.closest('.form-group').find('.warning');
      $warning.text("");
      $(".addStudentBtn").prop("disabled",false);
      $this.closest('.form-group').removeClass('has-error');

      var $hassError = false;

      /*If Roll Empty*/
      if ($this.val() == '' ) {
        $warning.text("Roll Can't be empty.");
        $hassError = true;
      }

      var $message = "Select ";

      /*If Class not selected*/
      if($("#admitClass").val() == null || $("#admitClass").val() == "" ){
        $message += "Class, ";
        $hassError = true;
      }

      /*If Year Not Selected*/
      if($("#stdCurntYear").val() == null || $("#stdCurntYear").val() == ""  ){
        $message += "Year, ";
        $hassError = true;
      }

      /*If Section Not Selected*/
      if($(".sectionSelect").prop('required') && $(".sectionSelect").val() == "" ){
        $message += "section ";
        $hassError = true;
      }


      if(!$hassError){
        var $siteUrl = $('#theSiteURL').text();
        $.ajax({
          url: $siteUrl+"/inc/ajaxAction.php",
          method: "POST",
          data: { class : $("#admitClass").val(), year: $("#stdCurntYear").val(), section: $(".sectionSelect").val(), roll: $this.val(),  type : 'checkRoll', std : $std },
          dataType: "json"
        }).done(function( msg ) {
          if(msg == 1){
            // $warning.text("This Roll already exists!");
            // $this.closest('.form-group').addClass('has-error');
            // $(".addStudentBtn").prop("disabled",true);
          }
        });
      }else{
        $message += "then input the Roll";
        $warning.text($message);
        $this.closest('.form-group').addClass('has-error');
        $(".addStudentBtn").prop("disabled",true);
      }

    });



    var media_uploader = '';
    $('.mediaUploader').click(function(event) {
      var $this = $(this);
      media_uploader = wp.media({
        frame:    "post",
        state:    "insert",
        multiple: false
      });

      media_uploader.on("insert", function(){
        var json = media_uploader.state().get("selection").first().toJSON();
        var image_url = json.url;
        var image_caption = json.caption;
        var image_title = json.title;
        $this.closest('.mediaUploadHolder').find('span').html("<img height='40' src='"+image_url+"'>");
        $this.closest('.mediaUploadHolder').find('.teacherImg').val(image_url);
      });

      media_uploader.open();
    });

    setTimeout( function(){
      $('.messageDiv').hide('slow');
    }  , 1000 );


  });

})( jQuery );



