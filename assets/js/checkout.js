jQuery( function ( $ ) {
	console.log(transactpay_args)
    var style = {
        css: { 
            border: 'none', 
            padding: '15px', 
            backgroundColor: '#000', 
            '-webkit-border-radius': '10px', 
            '-moz-border-radius': '10px', 
            opacity: .5, 
            color: '#fff' 
        },

    }

    $.blockUI({ 
        ...style,
        message: '<p> Please wait...</p>' 
    }); 

    let transactpay_timeoutid = setTimeout($.unblockUI, 4000);
	// $.blockUI({message: '<p> Please wait...</p>'});
	let payment_made = false;
	const redirectPost = function (location, args) {
		let form = "";
		$.each(args, function (key, value) {
			// value = value.split('"').join('\"')
			form += '<input type="hidden" name="' + key + '" value="' + value + '">';
		});
		$('<form action="' + location + '" method="POST">' + form + "</form>")
			.appendTo($(document.body))
			.submit();
	};

	const processData = () => {
		return {
            email: transactpay_args.email,
			amount: transactpay_args.amount,
            firstName: transactpay_args.first_name,
            lastName: transactpay_args.last_name,
			reference: transactpay_args.reference,
            merchantReference: transactpay_args.reference,
			currency: transactpay_args.currency,
            description: transactpay_args.description,
            apiKey: transactpay_args.public_key,
            encryptionKey: transactpay_args.encrypt_key,
            mobile: transactpay_args.phone_number,
            country: transactpay_args.country,
            onCompleted: function (response) {
				var tr = response.reference;
                console.log(response);
				if ( 'successful' === response.status.toLowerCase() ) {
					payment_made = true;
					$.blockUI({
                        ...style,
                        message: '<p> confirming transaction ...</p>'
                    });
					// redirectPost(transactpay_args.redirect_url + "?reference=" + tr, response);
				}
				// this.onClose(); // close modal
			},
			onClose: function (dd) {
				$.unblockUI();
				if (payment_made) {
					$.blockUI({ 
                        ...style, 
                        message: '<p> Confirming Transaction</p>'
                    });
					redirectPost(transactpay_args.redirect_url + "&reference=" + transactpay_args.reference, {});
				} else {
					$.blockUI({
                        ...style,
                        message: '<p> Canceling Payment</p>'
                    });
					window.location.href = transactpay_args.cancel_url;
				}
			}
		}
	}
	let payload = processData();

	try {
		const TransactpayCheckout = new window.CheckoutNS.PaymentCheckout((payload));
    	TransactpayCheckout.init();
	} catch (error) {
		if( error instanceof TypeError) {
			clearTimeout(transactpay_timeoutid);
			$.blockUI({
				...style,
				message: '<p> Unable to Connect to TransactPay. Kindly refresh your browser.</p>',
				overlayCSS: {
					backgroundColor: '#FFF',
					opacity: 0.6,
					cursor: 'wait'
				}
			});

		}
	}
	
} );