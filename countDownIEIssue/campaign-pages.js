jQuery(document).ready(function(){

		
    jQuery("body").addClass("campaign-pages");	
	
	var ifrmaepath = jQuery('#iframecontent').attr('src');
	var campaign_id = jQuery.urlParam('cmp');
	
	if (campaign_id) {
		appendpath = ifrmaepath + '&cmp=' + campaign_id;
	}
	else{
		appendpath = ifrmaepath + '&cmp=7010g000000vtyh';
	}
	jQuery('#iframecontent').attr('src', appendpath);
	
	
	
	var ifrmaepath = jQuery('#iframecontentc').attr('src');
	var campaign_id = jQuery.urlParam('cmp');
	
	if (campaign_id) {
		appendpath = ifrmaepath + '&cmp=' + campaign_id;
	}
	else{
		appendpath = ifrmaepath + '&cmp=7010g000000vuCU';
	}
	jQuery('#iframecontentc').attr('src', appendpath);
	
	
	var ifrmaepath = jQuery('#iframecontentcgxp').attr('src');
	var campaign_id = jQuery.urlParam('cmp');
	
	if (campaign_id) {
		appendpath = ifrmaepath + '&cmp=' + campaign_id;
	}
	else{
		appendpath = ifrmaepath + '&cmp=7010g000001MLjy';
	}
	jQuery('#iframecontentcgxp').attr('src', appendpath);
	



    /*----------Custom animation : 22-03-2019 -----------*/
//code to dynamic date and time
    
// Set the date we're counting down to
// Update the count down every 1 second
if(jQuery('#days_count1').length > 0 ){	



var countDownDate = 1579071659000;//new Date("Jan 14, 2020 00:00:00").getTime();
var countDownDateJs = new Date("Jan 14, 2020 00:00:00").getTime();
console.log('countDownDateJs'+countDownDateJs);//1578940200000

var nday = new ProgressBar.Circle(days, {
  strokeWidth: 12,
  easing: 'linear',
  duration: 1000,
  color: '#6ea516',
  trailColor: 'rgba(220,220,220,1)',
  trailWidth: 12,
  svgStyle: null
});

var nhour = new ProgressBar.Circle(hours, {
  strokeWidth: 12,
  easing: 'linear',
  duration: 1000,
  color: '#6ea516',
  trailColor: 'rgba(220,220,220,1)',
  trailWidth: 12,
  svgStyle: null
});

var nmin = new ProgressBar.Circle(minutes, {
  strokeWidth: 12,
  easing: 'linear',
  duration: 1000,
  color: '#6ea516',
  trailColor: 'rgba(220,220,220,1)',
  trailWidth: 12,
  svgStyle: null
});

var bar = new ProgressBar.Circle(seconds, {
  strokeWidth: 12,
  easing: 'linear',
  duration: 1000,
  color: '#6ea516',
  trailColor: 'rgba(220,220,220,1)',
  trailWidth: 12,
  svgStyle: null
});




	



	var x = setInterval(function() {


	// Get todays date and time
	var now = new Date().getTime();

	// Find the distance between now and the count down date
	var distance = countDownDate - now;

	// Time calculations for days, hours, minutes and seconds
	var days = Math.floor(distance / (1000 * 60 * 60 * 24));
	var days_percentage = Math.round( 100 - (days/365)*100 );


	var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
	var hours_percentage = Math.round(100 - (hours/24)*100 );

	var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
	var minutes_percentage = Math.round( 100 - (minutes/60)*100 );


	var seconds = Math.floor((distance % (1000 * 60)) / 1000);
	var seconds_percentage = Math.round(100 - (seconds/60)*100 );

	
		var bar_seconds_percentage = 1.0 - (seconds/60);
	
	var ndays_percentage = (days_percentage/100);
	var nhours_percentage = (hours_percentage/100);
	var nmin_percentage =  (minutes_percentage/100);


	nday.animate(ndays_percentage); 
	
	nhour.animate(nhours_percentage); 
	
	nmin.animate(nmin_percentage); 
	
	
	bar.animate(bar_seconds_percentage); 
	
	document.getElementById("days").setAttribute("data-percent", ndays_percentage);
	
	document.getElementById("hours").setAttribute("data-percent", nhours_percentage);
	document.getElementById("minutes").setAttribute("data-percent", nmin_percentage);
	
	document.getElementById("seconds").setAttribute("data-percent", bar_seconds_percentage);
	
	
	
	//console.log(days);
		document.getElementById("days_count").innerHTML =  days;
		document.getElementById("hours_count").innerHTML = hours;
		document.getElementById("minutes_count").innerHTML = minutes;
		document.getElementById("seconds_count").innerHTML = seconds;


	document.getElementById("days").setAttribute("data-percent", days_percentage);
		document.getElementById("hours").setAttribute("data-percent", hours_percentage);
		document.getElementById("minutes").setAttribute("data-percent", minutes_percentage);
		document.getElementById("seconds").setAttribute("data-percent", seconds_percentage);



	if (distance < 0) {
	clearInterval(x);
	document.getElementById("demo").innerHTML = "EXPIRED";
	}
		
	}, 1000);
}


});


jQuery('#downloadNow').click(function(){
	jQuery('html, body').animate({
		scrollTop: jQuery( jQuery(this).attr('href') ).offset().top - 140
	}, 500);
	return false;
});


    
    

