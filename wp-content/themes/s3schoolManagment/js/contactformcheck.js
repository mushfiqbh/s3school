$(document).ready(function(){

	$(function() {

        if( $('.form-check').length > 0) {

            $('.form-check').each(function(){

                var form = $(this),
                    btn = form.find('.btn-submit');

                btn.addClass('disabled');

                // check form fields for valid or notempty inputs
                function checkInput(){

                    form.find('.required-field').each(function(){

                        if($(this).hasClass('mailfield')) {

                            var mailfield = $(this);
                            var pattern = /^([a-z0-9_\.-])+@[a-z0-9-]+\.([a-z]{2,4}\.)?[a-z]{2,4}$/i;
                            if(pattern.test(mailfield.val())){
                                mailfield.removeClass('empty-field');
                            } else {
                                mailfield.addClass('empty-field');
                            }

                        } else if($(this).val() != '') {

                            $(this).removeClass('empty-field');

                        } else {

                            $(this).addClass('empty-field');
                        }

                    });
                }

                // Highlight empty or not valid fields
                function lightEmpty(){
                    form.find('.empty-field').addClass('required-field-error');
                    form.find('.empty-field').parents('.form-check-line').find('.required-field-error').css({'visibility':'visible'});
                    setTimeout(function(){
                        form.find('.empty-field').removeClass('required-field-error');
                        form.find('.empty-field').parents('.form-check-line').find('.required-field-error').css({'visibility':'hidden'});
                    },1000);
                }

                //  200ms form fields check
                setInterval(function(){
                    checkInput();
                    var sizeEmpty = form.find('.empty-field').length;
                    if(sizeEmpty > 0){
                        if(btn.hasClass('disabled')){
                            return false
                        } else {
                            btn.addClass('disabled')
                        }
                    } else {
                        btn.removeClass('disabled')
                    }
                },200);

                //  Button submit click
                btn.click(function(){
                    if($(this).hasClass('disabled')){
                        lightEmpty();
                        return false
                    } else {
                        form.submit();
                        $('.send-success').fadeIn("300", "linear");
                        form[0].reset();
                        setTimeout(function(){$('.send-success').fadeOut("300", "linear");}, 3000);
                    }
                });

            });

        }

	});

});