var count = 0; // <== make the variable global
jQuery(document).ready(function($) {

  // set an intervel. The callback gets executed every interval
  var setInterval1_ID = setInterval(triggerAjax, 10000); // 10 sec updates

  var timeout1_ID = setTimeout(stopSetInterval1, 100000); // this is 100 seconds for 10 updates

  $(document).on("click","#refresh-button",function() {
                                                          $("#refresh-button").addClass("fa-spin");
                                                          //
                                                           count = 0;
                                                           var thisCount = 0;

                                                           while (thisCount  <= 9)
                                                           {
                                                             thisCount++;
                                                             triggerAjax();
                                                           }
                                                       }
                );


   function stopSetInterval1() {
                                 // clear the interval trigger explicitly
                                 clearInterval(setInterval1_ID);

                                 // stop spinning of update wheel
                                 $("#refresh-button").removeClass("fa-spin");
                                 // remove animation on pv-solar-arrow
                                 $("#power-arrow-solar-animation").removeClass();
                                 // remove animation on inverter to home arrow
                                 $("#power-arrow-load-animation").removeClass();
                                 // remove animation on battery arrow
                                 $("#battery-arrow-load-animation").removeClass();
                                 // also clear the timeout
                                 clearTimeout(timeout1_ID);
                                };


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

                                                // change the arrow class for Inverter Pout to Home arrow using Ajax update
                                                $('#power-arrow-load').removeClass().addClass(data.inverter_pout_arrow_class);
                                                // Add the home load arrow animation
                                                $("#power-arrow-load-animation").removeClass().addClass("arrowSliding_nw_se");



                                                // Solar Power related values Ajax update
                                                //Change Solar output power value using Ajax delivered object data
                                                $('#power-solar').html(round(data.psolar_kw, 2) + ' kW<br>'  + '<font color="#D0D0D0">'
                                                                                          + data.solar_pv_adc + 'A');
                                                // todo need to add the SOlar-PB current at battery interface
                                                // update the arrow based on ajax
                                                $('#power-arrow-solar').removeClass().addClass(data.solar_arrow_class);
                                                // add solar arrow animation
                                                $("#power-arrow-solar-animation").removeClass().addClass(data.solar_arrow_animation_class);

                                                // Change the Battery values based on Ajax update
                                                $('#power-arrow-battery').removeClass().addClass(data.battery_charge_arrow_class);
                                                // update the battery animation class based on Ajax values
                                                $("#battery-arrow-load-animation").removeClass().addClass(data.battery_charge_animation_class);
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

                                                //
                                                count++; // <== update count
                                                if(count == 9)
                                                {
                                                    // remove spinner on update button
                                                    $("#refresh-button").removeClass("fa-spin");
                                                    // remove animation on pv-solar-arrow
                                                    $("#power-arrow-solar-animation").removeClass();
                                                    // remove animation on inverter to home arrow
                                                    $("#power-arrow-load-animation").removeClass();
                                                    // remove animation on battery arrow
                                                    $("#battery-arrow-load-animation").removeClass();
                                                }

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
