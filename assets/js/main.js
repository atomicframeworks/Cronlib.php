$(window).load(function() {
	var elem,index;
	
	// When our directive gains focus unlock it
	$('.deep-focus').focusin(function(){
	  $(this).closest('li').addClass('unlocked');
	});
	
	// When our directive loses focus lock it (remove unlocked state)
	$('.deep-focus').focusout(function(){
	  $(this).closest('li').removeClass('unlocked');
	});
	
	// Set form values
	$('form input').each(function(){
		var val = $(this).attr('value');
		$(this).val(val);
	});
	
	/* Allow user to focus on the input by clicking across the entire parent div */
	$('.command, .time').click(function () {
		$this = $(this);
		$this.children('input').focus();
	});
	
	// Bind click on trash can (delete the directive)	  
	$('.trash').click(function(){
	 $(this).closest('li').toggleClass('trashed');
	 if ( $(this).closest('li').hasClass('trashed') ){
	 	var val = '1';
	 }
	 else {
		var val = '0';
	 }
	 // Set value and attr
	 $(this).val(val);
	 $(this).attr('value',val);
	});
			  
	// Bind form keypress (will update input size dynamically & if press enter submit form)
	$('form input').bind('change keydown keyup',function(e){
		var $this = $(this);
        if(e.which == 13) {

			e.cancelBubble = true;
			e.returnValue = false;

			if (e.stopPropagation) {
				e.stopPropagation();
				e.preventDefault();
			}
			// Target the input and click it
			$this.parent().parent().children('div.utils').children('input[type="submit"]').focus().click();
			// Blur effect - We have to focusout on the form
            $this.parent().parent().focusout();
        }
		else{
			// Set input size to character size (grow input) while typing
			$this.attr('size', ($this.val().length)+1);
		}
	});
			  
	// Bind form submit button - submit via AJAX
	$('form input[type="submit"]').click(function(e){
		e.preventDefault();
		// Blur effect - We have to focusout on the form
        $(this).parent().parent().focusout();
  		var form = $(this).closest('form');
		$.ajax( {
			url : '/',
			type : 'post',          
			data: form.serialize(),
			success : function( data ) {
				console.log(data);
				eval(data);
							
			}
		});  
		return false;
	});

	/* Bind the Active switch - Toggles if commented / uncommented for the command in crontab */
	$('.switch').bind('click', function(){
		var $this = $(this);
		$this.toggleClass('active');
		if( $this.hasClass('active') ){
			$this.children('input').val('1');
			$this.closest('li').addClass('active');
		}
		else{
			$this.children('input').val('0');
			$this.closest('li').removeClass('active');
		}
	});

});