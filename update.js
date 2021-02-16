jQuery(document).ready(function($) {

  // set an intervel. The callback gets executed every interval
  var setInterval1_ID = setInterval(triggerAjax, 5000); // 10,000 is 10 seconds
                    // console.log('my_ajax_obj: ', my_ajax_obj);

  var timeout1_ID = setTimeout(stopSetInterval, 60000); // this is 120 seconds or 2 minutes for 12 updates

  function stopSetInterval() {
                                clearInterval(setInterval1_ID);
                                // stop spinning of update wheel
                                $('#refresh-button').removeClass().addClass('fa fa-1x fa-spinner');
                               };

 $('#refresh-button').on('click', function() {
                                               // set an intervel. The callback gets executed every interval
                                               var setInterval_ID2 = setInterval(triggerAjax, 5000); // 10,000 is 10 seconds
                                                                 // console.log('my_ajax_obj: ', my_ajax_obj);

                                               $('#refresh-button').removeClass().addClass('fa fa-1x fa-spinner fa-spin');

                                               var timeout2_ID = setTimeout(stopSetInterval(setInterval_ID2), 60000); // this is 120 seconds or 2 minutes for 12 updates

                                             });

  function triggerAjax() {

                            $.post(my_ajax_obj.ajax_url,
                            {                                 //POST request
                              _ajax_nonce: my_ajax_obj.nonce, //nonce extracted and sent
                              action: "get_studer_readings"  // hook added for action wp_ajax_get_studer_readings in php file
                            },
                              function(data) 	{				// data is JSON data sent back by server in response, wp_send_json($somevariable)
                                                // update the page with new readings. Lets just log the value sto see if we are getting good data
                                                // console.log('data: ', data);
                                                // console.log('battery html', $('#power-battery').html());

                                                //Change Inverter output power value using Ajax delivered object data
                                                $('#power-load').html( data.pout_inverter_ac_kw + ' kW');

                                                // change the arrow class for Inverter Pout to Home using Ajax update
                                                $('#power-arrow-load').removeClass().addClass(data.inverter_pout_arrow_class);


                                                // Solar Power related values Ajax update
                                                //Change Solar output power value using Ajax delivered object data
                                                $('#power-solar').html(round(data.psolar_kw, 2) + ' kW<br>'  + '<font color="#D0D0D0">'
                                                                                          + data.solar_pv_adc + 'A');
                                                // todo need to add the SOlar-PB current at battery interface
                                                // update the arrow based on ajax
                                                $('#power-arrow-solar').removeClass().addClass(data.solar_arrow_class);

                                                // Change the Battery values based on Ajax update
                                                $('#power-arrow-battery').removeClass().addClass(data.battery_charge_arrow_class);
                                                //Change Inverter output power value using Ajax delivered object data
                                                $('#power-battery').html(data.pbattery_kw + ' kW<br>'  + '<font color="#D0D0D0">'
                                                                                          + data.battery_voltage_vdc + 'V<br>'
                                                                                          + data.battery_charge_adc + 'A');

                                                //Change Grid AC in power and arrow calss based on Ajax updates
                                                //Change Inverter output power value using Ajax delivered object data
                                                $('#ppower-grid-genset').html(data.grid_pin_ac_kw + ' kW<br>'  + '<font color="#D0D0D0">'
                                                                                          + data.grid_input_vac + 'V<br>'
                                                                                          + data.grid_input_aac + 'A');
                                                // change the arrow class for Inverter Pout to Home using Ajax update
                                                $('#power-arrow-grid-genset').removeClass().addClass(data.grid_input_arrow_class);

                                              });
                        };
    function round(value, exp) {
    if (typeof exp === 'undefined' || +exp === 0)
      return Math.round(value);

    value = +value;
    exp = +exp;

    if (isNaN(value) || !(typeof exp === 'number' && exp % 1 === 0))
      return NaN;

    // Shift
    value = value.toString().split('e');
    value = Math.round(+(value[0] + 'e' + (value[1] ? (+value[1] + exp) : exp)));

    // Shift back
    value = value.toString().split('e');
    return +(value[0] + 'e' + (value[1] ? (+value[1] - exp) : -exp));
  }

});
