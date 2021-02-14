jQuery(document).ready(function($) {

  // set an intervel of 3s. The callback function that gets
  // executed at the end is passed as timingload() that needs to be defined
  // setInterval(timingLoad, 3000);
  //
  function timingLoad() {

    $.post(my_ajax_obj.ajax_url,
    {                                 //POST request
      _ajax_nonce: my_ajax_obj.nonce, //nonce extracted and sent
      action: "ajax_get_studer_readings"  // hook added for action wp_ajax_spzrbl_city in php file
    },
      function(data) 	{				// data is JSON data sent back by server in response, wp_send_json($somevariable)
        // update the page with new readings
      });
  };

});
