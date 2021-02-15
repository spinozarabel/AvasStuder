jQuery(document).ready(function($) {

  // set an intervel of 3s. The callback function that gets
  // executed at the end is passed as timingload() that needs to be defined
  //setInterval(timingLoad, 20000);
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
        console.log('data: ', data);

        //Change Inverter output power value using Ajax delivered object data
        $('#power-load').val(data.pout_inverter_ac_kw + ' kW');
        // change the arrow class for Inverter Pout to Home using Ajax update
        $('#power-arrow-load').removeClass().addClass(data.inverter_pout_arrow_class);

        // Solar Power related values Ajax update
        //Change Solar output power value using Ajax delivered object data
        $('#power-solar').val(data.psolar_kw + ' kW');
        // todo need to add the SOlar-PB current at battery interface
        // update the arrow based on ajax
        $('#power-arrow-solar').removeClass().addClass(data.solar_arrow_class);

        // Change the Battery values based on Ajax update
        $('#power-arrow-battery').removeClass().addClass(data.battery_charge_arrow_class);
        //Change Inverter output power value using Ajax delivered object data
        $('#power-battery').val(data.pbattery_kw + ' kW');

        //Change Grid AC in power and arrow calss based on Ajax updates
        //Change Inverter output power value using Ajax delivered object data
        $('#ppower-grid-genset').val(data.grid_pin_ac_kw + ' kW');
        // change the arrow class for Inverter Pout to Home using Ajax update
        $('#power-arrow-grid-genset').removeClass().addClass(data.grid_input_arrow_class);
      });
  };

});
