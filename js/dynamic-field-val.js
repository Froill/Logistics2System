
                // JavaScript to handle dynamic fields and validation
                document.addEventListener('DOMContentLoaded', function(){
                    const purposeSelect = document.getElementById('purposeSelect');
                    const purposeWrap = document.getElementById('purposeOtherWrap');
                    const purposeOther = document.getElementById('purposeOther');
                    const reqSelect = document.getElementById('requestedVehicleSelect');
                    const reqWrap = document.getElementById('requestedVehicleOtherWrap');
                    const reqOther = document.getElementById('requestedVehicleOther');
                    const form = document.getElementById('requestVehicleForm');
                    const reservationDate = document.getElementById('reservationDate');
                    const expectedReturn = document.getElementById('expectedReturn');
                    const todayDate = '<?= (new DateTime("now", new DateTimeZone("Asia/Manila")))->format("Y-m-d") ?>';

                    function togglePurpose(){
                        if (!purposeSelect) return;
                        if (purposeSelect.value === 'Other') { purposeWrap.style.display = 'block'; purposeOther.required = true; purposeOther.focus(); }
                        else { purposeWrap.style.display = 'none'; purposeOther.required = false; purposeOther.value = ''; }
                    }
                    function toggleReq(){
                        if (!reqSelect) return;
                        if (reqSelect.value === 'Other') { reqWrap.style.display = 'block'; reqOther.required = true; reqOther.focus(); }
                        else { reqWrap.style.display = 'none'; reqOther.required = false; reqOther.value = ''; }
                    }

                    if (purposeSelect) purposeSelect.addEventListener('change', togglePurpose);
                    if (reqSelect) reqSelect.addEventListener('change', toggleReq);

                    if (form) form.addEventListener('submit', function(e){
                        // If purpose is Other, replace select value with entered text
                        if (purposeSelect && purposeSelect.value === 'Other'){
                            const v = (purposeOther.value || '').trim();
                            if (!v){ e.preventDefault(); purposeOther.focus(); alert('Please specify purpose'); return false; }
                            if (!Array.from(purposeSelect.options).some(o => o.value === v)){
                                const opt = new Option(v, v, true, true);
                                purposeSelect.add(opt);
                            } else { purposeSelect.value = v; }
                        }
                        // If requested vehicle type is Other, replace select value
                        if (reqSelect && reqSelect.value === 'Other'){
                            const v = (reqOther.value || '').trim();
                            if (!v){ e.preventDefault(); reqOther.focus(); alert('Please specify vehicle type'); return false; }
                            if (!Array.from(reqSelect.options).some(o => o.value === v)){
                                const opt = new Option(v, v, true, true);
                                reqSelect.add(opt);
                            } else { reqSelect.value = v; }
                        }
                        // ensure expectedReturn is not before reservationDate
                        if (reservationDate && expectedReturn){
                            const resVal = reservationDate.value || todayDate;
                            if (expectedReturn.value && expectedReturn.value < resVal){
                                e.preventDefault();
                                alert('Completion Date cannot be before Reservation Date');
                                expectedReturn.focus();
                                return false;
                            }
                        }
                    });
                    // initialize min values and listeners
                    if (reservationDate){
                        reservationDate.min = todayDate;
                        reservationDate.addEventListener('change', function(){
                            const minVal = reservationDate.value || todayDate;
                            if (expectedReturn){
                                expectedReturn.min = minVal;
                                if (expectedReturn.value && expectedReturn.value < minVal) expectedReturn.value = minVal;
                            }
                        });
                    }
                    if (expectedReturn){ expectedReturn.min = todayDate; }
                });