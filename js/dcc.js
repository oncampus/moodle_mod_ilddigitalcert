var  pollinfo = document.getElementById('poll-info');

// TODO nach vorgegebener Zeit abbrechen
setInterval(function(){
    result = $.ajax({
        type: "POST",
        async: false,
        url: "dcconnectorpoll.php",
        data: ({
            action: "poll"
        })
    }).responseText;
    if (result != '') {
        check = JSON.parse(result);
        if (check.status == 'polling') {
            //pollinfo.innerHTML = 'Polling';
        } else if (check.status == 'request_accepted') {
            //pollinfo.innerHTML = 'Request accepted';
            window.location.reload();
        } else if (check.status == 'bad_request') {
            pollinfo.style.display = 'block';
            pollinfo.innerHTML = 'Bad request';
        }
    }
}, 5000);