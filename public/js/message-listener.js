function addProgress(StepPayWindow) {
    let div = document.createElement('div');

    StepPayWindow.appendChild(div);
    div.outerHTML = '<div class="steppay_payment_progress">' +
        '<img src="https://steppay.s3.ap-northeast-2.amazonaws.com/public/ordering1.gif">' +
        '<h1>결제가 진행중입니다.</h1>' +
        '<span>결제가 완료될때까지 다소 시간이 걸릴 수 있습니다.<br />창을 새로고침하거나, 닫지 마시고 잠시만 기다려주세요.</span>' +
        '</div>';
}

function process_payment(successResult, pg, redirectUrl = null) {
    let StepPayWindow = document.createElement('div');

    StepPayWindow.id = '_STEP_window';
    switch (pg) {
        case 'nice':
            document.body.appendChild(StepPayWindow);
            addProgress(StepPayWindow);

            const form = document.createElement('form');

            form.action = successResult.completionUrl;
            form.method = 'POST';

            const params = {
                orderId: successResult.orderId,
                nonce: successResult.nonce,
                idKey: successResult.response.idKey
            }
            if (redirectUrl !== null) {
                params['redirectUrl'] = redirectUrl;
            }
            for (let paramsKey in params) {
                const input = document.createElement('input');

                input.type = 'hidden';
                input.name = paramsKey;
                input.value = params[paramsKey];
                form.appendChild(input);
            }
            document.body.appendChild(form);
            form.submit();
            break;
        default: {
            const StepPay = SDK.StepPay;

            StepPay.requestPay(successResult).success((d)=>{
                document.body.appendChild(StepPayWindow);
                addProgress(StepPayWindow);

                const form = document.createElement('form');

                form.action = successResult.completionUrl;
                form.method = 'POST';

                const params = {
                    orderId: successResult.partner.partnerOrderId,
                    nonce: successResult.nonce,
                    idKey: d.idKey
                }
                if (typeof d.bankCode !== 'undefined') {
                    params['bankCode'] = d.bankCode;
                    params['accountBank'] = d.accountBank;
                    params['accountNum'] = d.accountNum;
                    params['accountName'] = d.accountName;
                }
                if (redirectUrl !== null) {
                    params['redirectUrl'] = redirectUrl;
                }
                for (let paramsKey in params) {
                    const input = document.createElement('input');

                    input.type = 'hidden';
                    input.name = paramsKey;
                    input.value = params[paramsKey];
                    form.appendChild(input);
                }
                document.body.appendChild(form);
                form.submit();
            }).error((d)=>{
                document.body.appendChild(StepPayWindow);

                const failedIframe = document.createElement('iframe');

                failedIframe.src = successResult.failedUrl + '?message=' + d;
                failedIframe.style.width = '100%';
                failedIframe.style.height = '100%';
                StepPayWindow.appendChild(failedIframe);

                const messageHandler = function(e) {
                    function IsJsonString(str) {
                        try {
                            JSON.parse(str);
                        } catch (e) {
                            return false;
                        }
                        return true;
                    }

                    self.paymentResult = IsJsonString(e.data) ? JSON.parse(e.data) : e.data;
                    if (self.paymentResult.action === 'close') {
                        StepPayWindow.remove();
                        window.removeEventListener('message', messageHandler);
                    }
                };
                window.addEventListener('message', messageHandler);
                stepPayProgress = false;
            })
            break;
        }
    }
}

jQuery(document).ready(function() {

    window.addEventListener("message", function(event) {
        let params = {};

        switch (event.data.action) {
            // case 'kakaopay': {
            //     let StepPayWindow = document.createElement('div');
            //
            //     StepPayWindow.id = '_STEP_window';
            //     document.body.appendChild(StepPayWindow);
            //
            //     var form = document.createElement('form');
            //
            //     form.setAttribute('charset', 'UTF-8');
            //     form.setAttribute('method', 'Post');
            //     form.setAttribute('action', window.stepcover.origin + '/wc-api/steppay_kakao');
            //
            //     var params = {
            //         orderId: event.data.orderId,
            //         nonce: window.stepcover.nonce,
            //         token: window.stepcover.token
            //     };
            //
            //     Object.keys(params).forEach(function (key) {
            //         var input = document.createElement('input');
            //
            //         input.setAttribute('name', key);
            //         input.setAttribute('value', params[key]);
            //         input.setAttribute('type', 'hidden');
            //         form.appendChild(input);
            //     });
            //     jQuery.ajax({
            //         url: window.stepcover.origin + '/wc-api/steppay_kakao',
            //         type: 'POST',
            //         datatype: 'json',
            //         data: jQuery(form).serialize(),
            //         success: function (json) {
            //             StepPayWindow.remove();
            //             process_payment(json, 'kakao', window.stepcover.origin + '/recover-complete?token=' + window.stepcover.token);
            //         }
            //     });
            //     break;
            // }
            // case 'nicepay': {
            //     let StepPayWindow = document.createElement('div');
            //
            //     StepPayWindow.id = '_STEP_window';
            //     document.body.appendChild(StepPayWindow);
            //
            //     var form = document.createElement('form');
            //
            //     form.setAttribute('charset', 'UTF-8');
            //     form.setAttribute('method', 'Post');
            //     form.setAttribute('action', window.stepcover.origin + '/wc-api/steppay_nice');
            //
            //     var params = {
            //         orderId: event.data.orderId,
            //         card_number: event.data.card_number,
            //         expiration_year: event.data.expiration_year,
            //         expiration_month: event.data.expiration_month,
            //         identifier: event.data.identifier,
            //         password: event.data.password,
            //         nonce: window.stepcover.nonce,
            //         token: window.stepcover.token,
            //         cardQuota: event.data.cardQuota
            //     };
            //
            //     Object.keys(params).forEach(function (key) {
            //         var input = document.createElement('input');
            //
            //         input.setAttribute('name', key);
            //         input.setAttribute('value', params[key]);
            //         input.setAttribute('type', 'hidden');
            //         form.appendChild(input);
            //     });
            //     jQuery.ajax({
            //         url: window.stepcover.origin + '/wc-api/steppay_nice',
            //         type: 'POST',
            //         datatype: 'json',
            //         data: jQuery(form).serialize(),
            //         success: function (json) {
            //             StepPayWindow.remove();
            //             process_payment(json, 'nice', window.stepcover.origin + '/recover-complete?token=' + window.stepcover.token + '&orderId=' + event.data.orderId);
            //         },
            //         error: function (json) {
            //             alert('결제를 실패했습니다. ' + json.responseJSON.data);
            //             StepPayWindow.remove();
            //             history.back();
            //         }
            //     });
            //     break;
            // }
            case 'payment-failed':
                params = {
                    action: 'stepcover_payment_failed',
                    nonce: window.stepcover.nonce,
                    reason: event.data.reason,
                    token: event.data.token
                };
                break;
            case 'payment-complete':
                params = {
                    action: 'stepcover_payment_complete',
                    orderId: event.data.orderId,
                    nonce: window.stepcover.nonce,
                    paymentInfo: event.data.paymentInfo,
                    paymentDate: event.data.paymentDate,
                    token: event.data.token,
                    idKey: event.data.idKey,
                };
                break;
            case 'change-date':
                params = {
                    action: 'stepcover_change_date',
                    orderId: event.data.orderId,
                    nonce: window.stepcover.nonce,
                    selectedDate: event.data.selectedDate,
                    token: event.data.token
                };
                break;
            // case 'change-date':
            //     window.location.href = '/change-date?token=' + new URL(document.location.href).searchParams.get('token');
            //     return;
            case 'change-method':
                jQuery.ajax({
                    url: window.stepcover.origin + '/wp-admin/admin-ajax.php',
                    type: 'POST',
                    data: {
                        action: 'stepcover_change_method',
                        token: event.data.token,
                        orderId: event.data.orderId,
                        nonce: window.stepcover.nonce
                    },
                    success: function(data) {
                        console.log(data);
                    }
                });
                return;
            case 'redirect':
                window.location.href = window.stepcover.origin;
                return;
            default:
                return;
        }

        var form = document.createElement('form');

        form.setAttribute('charset', 'UTF-8');
        form.setAttribute('method', 'Post');
        form.setAttribute('action', window.stepcover.origin + '/wp-admin/admin-ajax.php');
        Object.keys(params).forEach(function(key) {
            var input = document.createElement('input');

            input.setAttribute('name', key);
            input.setAttribute('value', params[key]);
            input.setAttribute('type', 'hidden');
            form.appendChild(input);
        });
        document.body.appendChild(form);
        form.submit();
    }, false);

});