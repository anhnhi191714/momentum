<div class="container container-donate-panel mb-3">
    <form method="post" onsubmit="return false;" class="form-donation">

    <div class="row_col_wrap_12" id="donation-block">
      <div class="vc_col-sm-6 wpb_column column_container vc_column_container col child_column">
        <div class="donate-leftblock p-4">
          <div class="form-row">
                  <div class="form-group col-md-6">
                    <label>First Name*</label>
                    <input type="text" class="form-control" id="firstname" required>
                  </div>
                  <div class="form-group col-md-6">
                    <label>Last Name*</label>
                    <input type="text" class="form-control" id="lastname" required>
                  </div>
            </div>
              <div class="form-row">
                  <div class="form-group col-md-6">
                    <label>Email Address*</label>
                    <input type="email" class="form-control" id="email" required>
                  </div>
                  <div class="form-group col-md-6">
                    <label>Phone*</label>
                    <input type="text" class="form-control" id="phone" required>
                  </div>
            </div>
              <div class="form-group mt-4">
                  <label>Postal Address*</label>
                  <input type="text" class="form-control" id="postaladdress" name="postaladdress" required>
              </div>
              <div class="form-row mt-4">
                  <div class="form-group col-md-6">
                  <div class="row">
                    <div class="col-sm-10 donateas">
                      <div>
                      I would like to donate as:
                      </div>
                      <div class="form-check">
                        <input class="form-check-input" type="radio" name="donateas" id="individual" value="individual" checked>
                        <label class="form-check-label">
                          Individual
                        </label>
                      </div>
                      <div class="form-check">
                        <input class="form-check-input" type="radio" name="donateas" id="organisation" value="organisation">
                        <label class="form-check-label">
                          Organisation
                        </label>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="form-group col-md-6">
                  <label>Company Name</label>
                  <input type="text" class="form-control" disabled id="company" name="company">
                </div>
            </div>
        </div>
      </div>
      
      <!-- Right Block -->
      
      <div class="vc_col-sm-6 wpb_column column_container vc_column_container col child_column">
        <div class="donate-rightblock p-4" style="font-size: 18px;">
        <div class="form-row">
          <div class="form-group col-md-6">
                    <label>Donation Amount*</label>
                    <input type="number" class="form-control" id="donation" name="donation_amount" placeholder="$" required>
                  </div>
                  <div class="donation-amount-space form-group col-md-3 col-xs-6">
                    <input type="radio" name="payas" id="one-off" value="One-off" checked>
                    <label class="form-check-label ml-2">
                      One Off
                    </label>
                  </div>
                  <div class="donation-amount-space form-group col-md-3 col-xs-6">
                    <input type="radio" name="payas" id="monthly" value="Monthly">
                    <label class="form-check-label ml-2">
                      Monthly
                    </label>
                  </div>
            </div>
            <div class="form-group">
                <div class="col-12">
                  <button name="submit" type="submit" class="btn w-100 font-weight-bold btn-donate">DONATE</button>
                </div>
            </div>
        </div>
      </div>
    </div>
    </form>       
</div>
<div class="donate-modal modal fade hidden" id="myModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-body" id="hco-embedded">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary modal-close" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
<div class="overlay hidden"></div>		
	<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-body" id="hco-embedded">
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>    	    
</div>

<script src="https://www.google.com/recaptcha/api.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/js/bootstrap.min.js" integrity="sha384-IDwe1+LCz02ROU9k972gdyvl+AESN10+x7tBKgc9I5HFtuNz0wWnPclzo6p9vxnk" crossorigin="anonymous"></script>
<script src="https://paymentgateway.commbank.com.au/static/checkout/checkout.min.js" data-complete="completePayment" data-error="errorCallback" data-cancel="cancelCallback" 
    data-beforeRedirect="Checkout.saveFormFields"
    data-afterRedirect="Checkout.restoreFormFields"></script>
<script src="<?php echo get_template_directory_uri(); ?>/assets/js/sweetalert2@9.js"></script>
<script src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
<script type="text/javascript">
    var successIndicator = '';
    var donationID = '';
    $(document).ready(function(){
        var ajaxurl = "<?php echo admin_url('admin-ajax.php');?>";
        $("#organisation").on("change", function() {
            if($(this).is(":checked")){
                $("#company").attr("disabled", false);
            }
        });
        $("#individual").on("change", function() {
            if($(this).is(":checked")){
                $("#company").attr("disabled", true);
            }
        });
        $("button[name=submit]").on("click",function(e){
            e.preventDefault();
            var empty = $(".form-donation").find("input:not(:disabled)").filter(function() {
                return this.value === "";
            });
            if(empty.length) {
                Swal.fire(
                    'Error!',
                    'Please check required fields',
                    'error'
                ); 
            }else{
                var donateAmount = $("input[name=donation_amount]").val();
                var settings_donation_checkout = {
                    "url": ajaxurl,
                    data : { 
                        action: 'comm_web_host_checkout_payment',
                        donation_amount: donateAmount,
                        firstname: $("#firstname").val(), 
                        lastname: $("#lastname").val(),
                        email: $("#email").val(),
                        phone: $("#phone").val(),
                        postaladdress: $("#postaladdress").val(),
                        donateas: $("input[name=donateas]").val(),
                        company: $("#company").val(),
                        donation_amount: $("input[name=donation_amount]").val(),
                        destination: $('input[name=destination]').val(),
                        mailchimp_opt: $("input[name=mailchimp-opt]").val()
                    },
                    "method": "POST",
                    "timeout": 0
                };
                $.ajax(settings_donation_checkout).done(function (response) {
                    var results = JSON.parse(response);
                    if(results.successIndicator){
                        successIndicator = results.successIndicator;
                        var session = results.session;
                        var sessionid = session.id;
                        var sessionVersion = session.version;
                        donationID = results.donationID;
                        Checkout.configure({
                            session: {
                                id:  sessionid,
                                version: sessionVersion
                            }
                        });
                        Checkout.showEmbeddedPage('#hco-embedded');
                        $('#myModal').modal('toggle');
                    }
                })
                .fail(function(response){
                    console.log(response);
                });
            }
        }); 
    });
    
    function errorCallback(error) {
        console.log(JSON.stringify(error));
    }
    function cancelCallback() {
        console.log('Payment cancelled');
        window.location.reload(true);
    }
    function completePayment(response){
        $('#myModal').modal('hide');
        Swal.fire(
            'Thank you!',
            'We are processing your donation...',
            'success'
        ); 
        var ajaxurl = "<?php echo admin_url('admin-ajax.php');?>";
        var result = response.split(",");
        var returnIndicator = result[0];
        if(returnIndicator == successIndicator){
            var update_payment = {
                    "url": ajaxurl,
                    data : { 
                        action: 'update_payment_status',
                        id: donationID
                    },
                    "method": "POST",
                    "timeout": 0
                };
            $.ajax(update_payment).done(function (response) {
                console.log(response);
                window.location.href="<?=site_url()?>/donate-thank-you/";
            })
            .fail(function(response){
                console.log(response);
            });
        }
    }
</script>

<script>
    $(document).ready(function(){
        $('.donate_destination').click(function(){
            $('.donate-box-button').removeClass('active');
            $(this).find('.donate-box-button').addClass('active');
            $('input[name=destination]').val($(this).data('text'));
            var diffHeading = $(this).siblings('input[name=diff-heading]');
            var diffContent = $(this).siblings('input[name=diff-content]');
            var diffImg = $(this).siblings('input[name=diff-img]');
            $('.donate-heading.desk').html(diffHeading.val());
            $('.donate-content.desk').html(diffContent.val());
            $('.featured-img-box').attr('src',diffImg.val());
        });
    });
</script>
<?php if(is_mobile_device()): ?>
    <script>
        $(document).ready(function(){
            $("#donation-block").appendTo('#donation-block-modal .modal-body');
            var acc = $(".accordion");
            acc.on("click", function() {
                acc.removeClass('active');
                $('.panel').slideUp();
                $(this).toggleClass('active');
                var diffImg = $(this).find('input[name=diff-img]');
                $('.featured-img-box').attr('src',diffImg.val());
                var panel = $(this).next();
                if (panel.css('display') == 'block') {
                    panel.css('display','none');
                } else {
                    panel.css('display','block');
                }
            });
        });
        
    </script>
<?php endif; ?>
<style>
    .swal2-container .swal2-styled.swal2-confirm{
        background-color: #b50c7a;
    }
</style>
<?php get_footer(); ?>