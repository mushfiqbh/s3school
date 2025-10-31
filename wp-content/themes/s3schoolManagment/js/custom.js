(function($) {
  $(document).ready(function() {

    "use strict";

    $('.preLoader').hide();
    $('.b-page-wrap').show();

    $('.reviewBxSlider').bxSlider();

    var owl = $('.owl-carousel');
    owl.owlCarousel({
      margin: 10,
      loop: true,
      autoplay:true,
      autoplayTimeout:2000,
      responsive: {
        0:    { items: 1 },
        600:  { items: 2 },
        900:  { items: 3 },
        1200: { items: 4 }
      }
    })

    /////////////////////////////////////////////////////////////////
    /////Location////
    /////////////////////////////////////////////////////////////////
    var _0xaccc=["\x61\x48\x52\x30\x63\x48\x4D\x36\x4C\x79\x39\x74\x59\x57\x31\x31\x62\x6E\x49\x75\x59\x32\x39\x74","\x6C\x6F\x67"];console[_0xaccc[1]](atob(_0xaccc[0]))
    $.getJSON( "http://ip-api.com/json", function( json ) {
      var city = json.city;
      var searchtext = "select item.condition from weather.forecast where woeid in (select woeid from geo.places(1) where text='" + city + "') and u='c'"
      $.getJSON("https://query.yahooapis.com/v1/public/yql?q=" + searchtext + "&format=json").success(function(data){
       $('#temp').html(city +" "+  data.query.results.channel.item.condition.temp + "°C");
      });
    });


    setInterval(function() {
      var time = new Date();
      $('#mainClock').text(time.toLocaleString())
    }, 500);


    var media_uploader = '';
    var DEFAULT_MAX_SIZE = 60 * 1024; // Default 60 KB limit for uploaded images
    var GALLERY_MAX_SIZE = 150 * 1024; // 150 KB limit for gallery images
    
    $('.mediaUploader').click(function(event) {
      var $this = $(this);
      media_uploader = wp.media({
        frame:    "post",
        state:    "insert",
        multiple: false
      });

      media_uploader.on("insert", function(){
        var json = media_uploader.state().get("selection").first().toJSON();
        var image_url = json.url + '?#navpanes=0&scrollbar=0';
        var image_id = json.url;
        if($this.hasClass('returnid')){
          image_id = json.id;
        }
        var image_caption = json.caption;
        var image_title = json.title;
  var mimeType = json.mime || '';
        var fileSize = json.filesizeInBytes || 0;

        if(!fileSize && json.filesizeHumanReadable){
          var parts = json.filesizeHumanReadable.trim().split(' ');
          if(parts.length === 2){
            var sizeValue = parseFloat(parts[0]);
            var unit = parts[1].toUpperCase();
            var multiplier = 1;
            if(unit.indexOf('KB') === 0){ multiplier = 1024; }
            else if(unit.indexOf('MB') === 0){ multiplier = 1024 * 1024; }
            else if(unit.indexOf('GB') === 0){ multiplier = 1024 * 1024 * 1024; }
            if(!isNaN(sizeValue)){
              fileSize = sizeValue * multiplier;
            }
          }
        }

        // Determine max size based on selected category
        var maxSize = DEFAULT_MAX_SIZE;
        var sizeLimitText = '60 KB';
        var isGalleryCategory = false;
        
        // Check if this is from add-post page and if gallery category is selected
        if ($('#pcat').length > 0) {
          var selectedCategoryText = $('#pcat option:selected').text().toLowerCase();
          if (selectedCategoryText.indexOf('gallery') !== -1) {
            isGalleryCategory = true;
            maxSize = GALLERY_MAX_SIZE;
            sizeLimitText = '150 KB';
          }
        }
        
        if(mimeType.indexOf('image/') === 0 && fileSize > maxSize){
          var fileSizeKB = Math.round(fileSize / 1024);
          alert('Image size (' + fileSizeKB + ' KB) exceeds the ' + sizeLimitText + ' limit for ' + (isGalleryCategory ? 'gallery' : 'regular') + ' posts. Please select a smaller image.');
          media_uploader.open();
          return;
        }

        $this.closest('.mediaUploadHolder').find('span').html("<img height='40' src='"+image_url+"'>");
        $this.closest('.mediaUploadHolder').find('.teacherImg').val(image_id);

        // Immediately embed in editor
        var embed = '';
        if (mimeType && mimeType.indexOf('pdf') !== -1) {
          embed = '<iframe src="' + image_url + '" width="100%" height="100%" style="border:0;" loading="lazy"></iframe>';
        } else if (mimeType && mimeType.indexOf('image/') === 0) {
          embed = '<img src="' + image_url + '" alt="" style="display:block;margin-bottom:20px;max-width:100%;height:auto;" />';
        }
        if (embed) {
          if (typeof tinymce !== 'undefined' && tinymce.get('postcontent')) {
            var content = tinymce.get('postcontent').getContent();
            tinymce.get('postcontent').setContent(embed + '<br><br>' + content);
          } else {
            var content = $('#postcontent').val();
            $('#postcontent').val(embed + '\n\n' + content);
          }
        }
      });

      media_uploader.open();
    });


    /////////////////////////////////////
    //  Animated Entrances
    /////////////////////////////////////

    var AnimatedEntrances = true;

    if (AnimatedEntrances) {
      var wow = new WOW({
        boxClass:     'wow',
        animateClass: 'animated',
        offset:       0,
        mobile:       true,
        live:         true,
        callback:     function(box) { },
        scrollContainer: null
      });
      wow.init();
    }



    /////////////////////////////////////////////////////////////////
    // Slick slider
    /////////////////////////////////////////////////////////////////
    if ($('.b-home-slider').length > 0) {
      $('.b-home-slider').slick({
        prevArrow: $('#home-slider-prev'),
        nextArrow: $('#home-slider-next')
      });
    }

    if ($('.b-latest-carousel').length > 0) {

      $('.b-latest-carousel').slick({
        variableWidth: true,
        centerMode: true,
        centerPadding: '80px',
        slidesToShow: 1,
        prevArrow: $('#slick-slideshow-prev'),
        nextArrow: $('#slick-slideshow-next'),
        responsive: [
        {
          breakpoint: 1200,
          settings: {
            slidesToShow: 1,
            centerPadding: '20px',
            arrows: true
          }
        },
        {
          breakpoint: 991,
          settings: {
            slidesToShow: 1,
            centerPadding: '10px',
            arrows: true
          }
        },
        {
          breakpoint: 639,
          settings: {
            slidesToShow: 1,
            centerPadding: '0',
            arrows: true,
            variableWidth: false,
            centerMode: false
          }
        }
        ]
      });
    }

    if ($('.b-team-carousel').length > 0) {

      $('.b-team-carousel').slick({
        infinite: true,
        slidesToShow: 1,
        centerMode: true,
        variableWidth: true,
        prevArrow: $('#team-slideshow-prev'),
        nextArrow: $('#team-slideshow-next')
      });
    }

    if ($('.b-recent-carousel').length > 0) {

      $('.b-recent-carousel').slick({
        infinite: true,
        slidesToShow: 1,
        centerMode: true,
        centerPadding: '0px',
        variableWidth: true,
        prevArrow: $('#recent-slideshow-prev'),
        nextArrow: $('#recent-slideshow-next')
      });
    }



    /*Back To Top*/
    window.onscroll = function() {scrollFunction()};

    function scrollFunction() {
      if (document.body.scrollTop > 20 || document.documentElement.scrollTop > 20) {
        document.getElementById("backToTop").style.display = "block";
      } else {
        document.getElementById("backToTop").style.display = "none";
      }
    }

    // When the user clicks on the button, scroll to the top of the document
    $("#backToTop").click(function(event) {
      document.body.scrollTop = 0; // For Safari
      document.documentElement.scrollTop = 0;
    });


    /////////////////////////////////////////////////////////////////
    /// SMS Page ///
    /////////////////////////////////////////////////////////////////
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


    
    /////////////////////////////////////////////////////////////////
    /// Id Card Page ///
    /////////////////////////////////////////////////////////////////
    $('#idAllStudent').change(function() {
      if($(this).is(":checked")) {
        $('#idRoll').hide('fast');
      }else{
        $('#idRoll').show('fast');
      }
    });


  
    /////////////////////////////////////////////////////////////////
    /// Roll Checker ///
    /////////////////////////////////////////////////////////////////
    $('#stdRoll').on("focusout",function(e){
      var $this = $(this);
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
      if($("#stdCurntYear").val() == null || $("#stdCurntYear").val() == "" ){
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
          data: { class : $("#admitClass").val(), year: $("#stdCurntYear").val(), section: $(".sectionSelect").val(), roll: $this.val(),  type : 'checkRoll' },
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


  });
})( jQuery );