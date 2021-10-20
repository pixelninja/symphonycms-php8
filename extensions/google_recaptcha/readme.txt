# Symphony CMS -Google reCaptcha extension

##Installation

- Generate a site key at <https://www.google.com/recaptcha>
- Add Generated reCaptcha secret ID and reCaptcha site key on Symphony preferences

##Add this code to the head of the website

<script src='https://www.google.com/recaptcha/api.js'></script>

##Add this code to the form

    <div data-callback="recaptcha_callback" class="g-recaptcha" data-sitekey="{/data/params/recaptcha-sitekey}"></div>
    <input class="recaptcha_class" name="fields[google_recaptcha]" type="hidden" value="" />



##Add this JavaScript to your site


	//reCaptcha callback function
 		function recaptcha_callback(){
		  	var g_recaptcha_key = $("#g-recaptcha-response").val();
		  	$(".recaptcha_class").val(g_recaptcha_key);		  	
      }


## Add filter to event

Add the reCAPTCHA Verification filter to your event your form is executing


