var pollinfo = document.getElementById('poll-info');

// TODO nach vorgegebener Zeit abbrechen
setInterval(async function () {
    result = await postData('dcconnectorpoll.php', {
        action: "poll"
    });
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

async function postData(url, data, jsonResponse = false) {
    const response = await fetch(url, {
        method: 'POST',
        body: new URLSearchParams(data)
    }).catch((error) => {
        console.error('Error:', error);
    });
    if (jsonResponse) {
        return response.json();
    }
    return response.text();
}