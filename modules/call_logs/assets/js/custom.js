    var callStatus = $("#userphone");
    var answerButton = $("#answer-button");
    var hangUpButton = $("#endcall");
    var callCustomerButtons = $("#startcall");
    var device = null;

    function updateCallStatus(status) {
    	callStatus.val('');
    	callStatus.attr('placeholder', status);
    }

    $(document).ready(function(){
    	setupClient();
    	$('#endcall').on('click',function(){
    		var userphone = document.getElementById('userphone').value;
    		var params = {"phoneNumber": userphone};
    		$('#startcall').show();
    		$('#endcall').hide();
    		document.getElementById("call_end_time").readOnly = false;
    		var dt = new Date();
    		var time = dt.getHours() + ":" + dt.getMinutes() + ":" + dt.getSeconds();
    		var month = dt.getMonth()+1;
    		var day = dt.getDate();
    		var fdate = dt.getFullYear() + '-' +
    		((''+month).length<2 ? '0' : '') + month + '-' +
    		((''+day).length<2 ? '0' : '') + day;
    		var fulldate = fdate + ' '+ time;
    		$('#call_end_time').val(fulldate);
    		device.disconnectAll();
    	}); 
    })
    function callCustomer()
    {
    	var userphone = document.getElementById('userphone').value;
    	if(userphone=='')
    	{
    		alert('please enter your phone number');
    	}
    	else
    	{
    		updateCallStatus("Calling " + userphone + "...");
    		var params = {"phoneNumber": userphone};
    		device.connect(params);
    	}
    }

	// new code
	function setupHandlers(device) {
		device.on('ready', function (_device) {
			updateCallStatus("Ready");
		});

		/* Report any errors to the call status display */
		device.on('error', function (error) {
			updateCallStatus("ERROR: " + error.message);
		});

		/* Callback for when Twilio Client initiates a new connection */
		device.on('connect', function (connection) {
        // Enable the hang up button and disable the call buttons
        hangUpButton.show();
        callCustomerButtons.hide();
        /*callSupportButton.prop("disabled", true);*/
        answerButton.hide();

        // If phoneNumber is part of the connection, this is a call from a
        // support agent to a customer's phone
        if ("phoneNumber" in connection.message) {
        	updateCallStatus("In call with " + connection.message.phoneNumber);
        } else {
            // This is a call from a website user to a support agent
            updateCallStatus("In call with support");
        }
    });

		/* Callback for when a call ends */
		device.on('disconnect', function(connection) {
        // Disable the hangup button and enable the call buttons
        hangUpButton.hide();
        callCustomerButtons.show();
        /*callSupportButton.prop("disabled", false);*/
        updateCallStatus("Ready");
    });

		/* Callback for when Twilio Client receives a new incoming call */
		device.on('incoming', function(connection) {
			updateCallStatus("Incoming support call");

        // Set a callback to be executed when the connection is accepted
        connection.accept(function() {
        	updateCallStatus("In call with customer");
        });

        // Set a callback on the answer button and enable it
        answerButton.click(function() {
        	connection.accept();
        });
        answerButton.show();
    });
	};
	function setupClient() {
	    
		$.post(admin_url+'call_logs/newToken', {
			forPage: window.location.pathname,
		}).done(function (data) {
		// Set up the Twilio Client device with the token
		device = new Twilio.Device();
		let obj = JSON.parse(data);
		device.setup(obj.token, { debug: true });
		setupHandlers(device);
	}).fail(function () {
		updateCallStatus("Could not get a token from server!");
	});
};
/* Call the support_agent from the home page */
function callSupport() {
    updateCallStatus("Calling support...");

    // Our backend will assume that no params means a call to support_agent
    device.connect();
};