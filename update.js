jQuery(document).ready(function($) {

  // set an intervel of 3s. The callback function that gets
  // executed at the end is passed as timingload() that needs to be defined
  setInterval(timingLoad, 10000);
  // console.log('my_ajax_obj: ', my_ajax_obj);

  //
  function timingLoad() {

    $.post(my_ajax_obj.ajax_url,
    {                                 //POST request
      _ajax_nonce: my_ajax_obj.nonce, //nonce extracted and sent
      action: "get_studer_readings"  // hook added for action wp_ajax_get_studer_readings in php file
    },
      function(data) 	{				// data is JSON data sent back by server in response, wp_send_json($somevariable)
        // update the page with new readings. Lets just log the value sto see if we are getting good data
        // console.log('data: ', data);
        var powerLoad = $('#power-load');	// select form element with id="power-load"
            powerLoad.val(data.pout_inverter_ac_kw + ' kW');

        $('#power-arrow-load').removeClass().addClass(data.inverter_pout_arrow_class);
      });
  };

});
