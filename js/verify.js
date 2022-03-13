var botti_1 = document.getElementById('botti_blockchain_check_1');
var botti_2 = document.getElementById('botti_blockchain_check_2');
var botti_3 = document.getElementById('botti_blockchain_check_3');
var botti_4 = document.getElementById('botti_blockchain_check_4');
var botti_5 = document.getElementById('botti_blockchain_check_5');
var botti_6 = document.getElementById('botti_blockchain_check_6');
var h_1 = document.getElementById('h1');
var p_hash = document.getElementById('p-hash');
var p_1 = document.getElementById('p1');
var p_2 = document.getElementById('p2');
var p_3 = document.getElementById('p3');
var p_4 = document.getElementById('p4');
var p_5 = document.getElementById('p5');
var p_6 = document.getElementById('p6');
var textbox = document.getElementById('textbox');
var assertionPage = document.getElementById('certpageiframe');
var sleepTime = 1000;

var dropZone = document.getElementById('dropzone');
var verifydiv = document.getElementById('verifydiv');

var imgError1 = document.getElementById('img-error-1');
var imgCheckLoad1 = document.getElementById('img-check-load-1');
var imgCheckLoad2 = document.getElementById('img-check-load-2');
var imgCheckLoad3 = document.getElementById('img-check-load-3');
var imgCheckLoad4 = document.getElementById('img-check-load-4');
var imgCheckLoad1Url = imgCheckLoad1.src;
var imgCheckLoad2Url = imgCheckLoad2.src;
var imgCheckLoad3Url = imgCheckLoad3.src;
var imgCheckLoad4Url = imgCheckLoad4.src;

var divCertData = document.getElementById('certdata');
var spanResultHash = document.getElementById('span-result-hash');
var spanResultStart = document.getElementById('span-result-start');
var prespanResultEnd = document.getElementById('prespan-result-end');
var spanResultEnd = document.getElementById('span-result-end');
var spanResultInstitution = document.getElementById('span-result-institution');

var imgLoader = document.getElementById('loader');
var asText = document.getElementById('asText');

var verificationStepUrl = 'return_verificationstep.php';
url = window.location.href;
if (url.includes('course/view.php')) {
    var verificationStepUrl = url.substr(0, url.indexOf('course/view.php')) + 'mod/ilddigitalcert/return_verificationstep.php';
}
else {
    var verificationStepUrl = url.substr(0, url.indexOf('mod/ilddigitalcert')) + 'mod/ilddigitalcert/return_verificationstep.php';
}

function getUrlParameterByName(name, url) {
    if (!url) {
        url = window.location.href;
    }
    name = name.replace(/[\[\]]/g, "\\$&");
    var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"), results = regex.exec(url);
    if (!results) {
        return null;
    }
    if (!results[2]) {
        return '';
    }
    return decodeURIComponent(results[2].replace(/\+/g, " "));
}

function IsJsonString(str) {
    try {
        JSON.parse(str);
    } catch (e) {
        return false;
    }
    return true;
}

processHashFromUrlParam(getUrlParameterByName('hash'));

function activateDropZone(activate = true) {
    if (activate) {
        dropZoneIsActive = true;
        dropZone.addEventListener('dragover', handleDragOver, false);
        dropZone.addEventListener('drop', dateiauswahl, false);
        dropZone.addEventListener('dragleave', handleDragLeave, false);
        asText.addEventListener('change', dropZoneChange, false);
    }
    else {
        dropZoneIsActive = false;
        dropZone.removeEventListener('dragover', handleDragOver, false);
        dropZone.removeEventListener('drop', dateiauswahl, false);
        dropZone.removeEventListener('dragleave', handleDragLeave, false);
        asText.removeEventListener('change', dropZoneChange, false);
    }
}

async function dropZoneChange(e) {
    console.log('dropZoneChange');
    resetBotti();
    verifydiv.scrollIntoView();
    var file = asText.files[0];
    var meta = '';
    botti_1.style.display = "block";
    h_1.style.display = "block";
    h_1.innerHTML += ' ' + file.name;
    p_1.style.display = "block";
    if (file.type == 'application/pdf') { // PDF!
        let formData = new FormData();
        formData.append('action', 'pdf');
        formData.append('file', file, file.name);
        console.log('verificationStepUrl: ' + verificationStepUrl);
        meta = $.ajax({
            type: 'POST',
            async: false,
            url: verificationStepUrl,
            data: formData,
            contentType: false,
            cache: false,
            processData:false
        }).responseText;
        await sleep(sleepTime);
        processMeta(meta);
    }
    else if (file.type == 'application/json' || file.name.match('.bcrt')) { // JSON!
        var reader = new FileReader();

        reader.onload = async function(e) {
            meta = reader.result;
            await sleep(sleepTime);
            processMeta(meta);
        }
        reader.readAsText(file);
    }
    else {
        await sleep(sleepTime);
        p_6.style.display = "block";
    }
}

async function dateiauswahl(evt) {
    resetBotti();
    activateDropZone(false);
    verifydiv.scrollIntoView();
    botti_1.style.display = "block";
    h_1.style.display = "block";
    p_1.style.display = "block";
    evt.stopPropagation();
    evt.preventDefault();

    var result = 'error';
    var gewaehlteDateien = evt.dataTransfer.files; // FileList Objekt.

    if (gewaehlteDateien.length == 0) {
        console.log('Error: no file!');
    }
    else {
        h_1.innerHTML += ' ' + gewaehlteDateien[0].name;

        if (gewaehlteDateien[0].type == 'application/pdf') { // PDF!
            let formData = new FormData();
            formData.append('action', 'pdf');
            formData.append('file', gewaehlteDateien[0], gewaehlteDateien[0].name);
            result = $.ajax({
                type: 'POST',
                async: false,
                url: verificationStepUrl,
                data: formData,
                contentType: false,
                cache: false,
                processData:false
            }).responseText;
        }
        else if (gewaehlteDateien[0].type == 'application/json' || gewaehlteDateien[0].name.match('.bcrt')) { // JSON!
            let formData = new FormData();
            formData.append('action', 'validateJSON');
            formData.append('file', gewaehlteDateien[0], gewaehlteDateien[0].name);
            result = $.ajax({
                type: 'POST',
                async: false,
                url: verificationStepUrl,
                data: formData,
                contentType: false,
                cache: false,
                processData:false
            }).responseText;
            console.log(result);
        }
        else if (gewaehlteDateien[0].type == 'application/xml' || gewaehlteDateien[0].name.match('.xml')) { // JSON!
            let formData = new FormData();
            formData.append('action', 'validateEDCI');
            formData.append('file', gewaehlteDateien[0], gewaehlteDateien[0].name);
            result = $.ajax({
                type: 'POST',
                async: false,
                url: verificationStepUrl,
                data: formData,
                contentType: false,
                cache: false,
                processData:false
            }).responseText;
            console.log(result);
        }
    }
    if (result == 'error') {
        await sleep(sleepTime);
        p_6.style.display = "block";
        activateDropZone();
    }
    else {
        await sleep(sleepTime);
        processMeta(result);
    }
}

function handleDragOver(evt) {
    evt.stopPropagation();
    evt.preventDefault();
    evt.dataTransfer.dropEffect = 'copy';
    dropZone.style.border = '2px dashed #106F6F';
}

function handleDragLeave(evt) {
    dropZone.style.border = '2px dashed #bfbfbf';
}
activateDropZone();

function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

function resetBotti() {
    imgCheckLoad1.src = imgCheckLoad1Url + "?a=" + Math.random();
    imgCheckLoad2.src = imgCheckLoad2Url + "?a=" + Math.random();
    imgCheckLoad3.src = imgCheckLoad3Url + "?a=" + Math.random();
    imgCheckLoad4.src = imgCheckLoad4Url + "?a=" + Math.random();

    textbox.innerHTML = '';
    botti_1.style.display = "none";
    h_1.style.display = "none";
    p_hash.style.display = "none";
    h_1.innerHTML = "Verifiziere" // TODO: Get text from moodle language files.
    p_1.style.display = "none";
    botti_2.style.display = "none";
    p_2.style.display = "none";
    botti_3.style.display = "none";
    p_3.style.display = "none";
    botti_4.style.display = "none";
    p_4.style.display = "none";
    botti_5.style.display = "none";
    p_5.style.display = "none";
    botti_6.style.display = "none";
    p_6.style.display = "none";
    assertionPage.style.display = "none";
    window.frames[0].document.body.innerHTML = '';
    assertionPage.style.display = 'none';

    spanResultHash.innerHTML = '';
    spanResultStart.innerHTML = '';
    prespanResultEnd.style.display = "none";
    spanResultEnd.innerHTML = '';
    spanResultInstitution.innerHTML = '';
    divCertData.style.display = "none";
    dropZone.style.border = '2px dashed #bfbfbf';
}

function showCert(meta) {
    metaJSON = JSON.parse(meta);
    base64String = metaJSON['extensions:assertionpageB4E'].assertionpage;

    result = $.ajax({
        type: 'POST',
        async: false,   // Important!
        url: verificationStepUrl,
        data: ({
            action: 'baseString',
            base64String: base64String
        })
    }).responseText;
    assertionPage.style.display = "block";
    window.frames[0].document.body.innerHTML = result;
    scaleIFrame();
    verifydiv.scrollIntoView();
    heightPx = assertionPage.contentWindow.document.body.scrollHeight + 80;
    assertionPage.style.height = heightPx + 'px';
}

async function processHash(hash, meta = null) {
    var result = '';
    botti_2.style.display = "none";
    botti_3.style.display = "block";
    p_3.style.display = "block";
    // Search hash in blockchain.
    result = $.ajax({
        type: 'POST',
        async: false,   // Important!
        url: verificationStepUrl,
        data: ({
            action: 'hash',
            hash: hash
        })
    }).responseText;
    console.log(result);
    cert = JSON.parse(result);
    await sleep(sleepTime);
    if (cert.institution == '0x0000000000000000000000000000000000000000') {
        // Hash not found in blockchain!
        p_6.style.display = "block";
        botti_3.style.display = "none";
        botti_6.style.display = "block";
        activateDropZone();
        return;
    }

    // Check validity.
    botti_3.style.display = "none";
    botti_4.style.display = "block";
    p_4.style.display = "block";
    // Invalid: {"institution":"0x0000000000000000000000000000000000000000","institutionProfile":"0","startingDate":"0","endingDate":"0","onHold":"0","valid":false}.
    // Valid: {"institution":"0x0000000000000000000000000000000000000000","institutionProfile":"0x0000000000000000000000000000000000000000000000000000000000000000","startingDate":"1530396000000000000","endingDate":"1688076000000000000","onHold":"0","valid":true}.
    await sleep(sleepTime);
    if (cert.valid == false) {
        // Invalid!
        p_6.style.display = "block";
        botti_4.style.display = "none";
        botti_6.style.display = "block";
    }
    else {
        // Valid!
        p_5.style.display = "block";
        botti_4.style.display = "none";
        botti_5.style.display = "block";
        // Show certificate details.
        divCertData.style.display = "block";
        spanResultHash.innerHTML = hash;
        let startDate = new Date(cert.startingDate);
        let endDate = new Date(cert.endingDate);
        spanResultStart.innerHTML = cert.startingDate;
        if (cert.endingDate != 'false') {
            prespanResultEnd.style.display = "block";
            spanResultEnd.innerHTML = cert.endingDate;
        }
        spanResultInstitution.innerHTML = imgLoader.outerHTML;
        institution = $.ajax({
            type: 'POST',
            async: false,   // Important!
            url: verificationStepUrl,
            data: ({
                action: 'institution_profile',
                institution_profile: cert.institutionProfile,
                meta: meta
            })
        }).responseText;
        if (!institution) {
            // TODO: spanResultInstitution.innerHTML = '<span>Not found in IPFS!</span>'; // TODO.
        }
        else {
            institution = JSON.parse(institution);
            spanResultInstitution.innerHTML = '<a href="' + institution.url + '"><img title="' + institution.name + '" alt="' + institution.description + '" height="50px" src="' + institution.image + '"/></a>';
        }
        divCertData.scrollIntoView();
        // TODO onhold, expired.
        await sleep(sleepTime);
        if (meta) { // If metadata available, show certificate.
            showCert(meta);
        }
        else { // Look for cert in DB.
            result = $.ajax({
                type: 'POST',
                async: false,   // Important!
                url: verificationStepUrl,
                data: ({
                    action: 'cert',
                    hash: hash
                })
            }).responseText;
            if (result) {
                console.log('found metadata');
                if (!institution) {
                    // Get institution from Metadata.
                    resultJSON = JSON.parse(result);
                    spanResultInstitution.innerHTML = '<a href="' + resultJSON.badge.issuer.url + '"><img title="' + resultJSON.badge.issuer.name + '" alt="' + resultJSON.badge.issuer.description + '" height="50px" src="' + resultJSON.badge.issuer.image + '"/></a>';
                }
                // Show cert.
                showCert(result);
            }
            else {
                if (!institution) {
                    spanResultInstitution.innerHTML = '<span>Not found in IPFS!</span>'; // TODO language files.
                }
                console.log('no metadata');
            }
        }
    }
    activateDropZone();
}

async function processMeta(meta) {
    var result = '';
    // TODO: Validate!!!
    if (!IsJsonString(meta)) {
        console.log('Invalid JSON!');
        p_6.style.display = "block";
        botti_1.style.display = "none";
        botti_6.style.display = "block";
        activateDropZone();
        return;
    }
    // GEnerate hash.
    botti_1.style.display = "none";
    botti_2.style.display = "block";
    p_2.style.display = "block";
    result = $.ajax({
        type: 'POST',
        async: false,   // Important!
        url: verificationStepUrl,
        data: ({
            action: 'meta',
            meta: meta
        })
    }).responseText;
    await sleep(sleepTime);
    var hash = result;
    console.log(hash);
    processHash(hash, meta);
}

function scaleIFrame() {
    eleStyle = window.getComputedStyle(assertionPage);
    scale = ((100 / 800) * eleStyle.width.substr(0, eleStyle.width.length - 2)) / 100;
    assertionPage.style.zoom = scale;
    assertionPage.style.transform = "scale(" + scale + ")";
    assertionPage.style.transformOrigin = "0 0";
    assertionPage.style.webkitTransform = "scale(" + scale + ")";
    assertionPage.style.webkitTransformOrigin = "0 0";
}

window.onresize = function() {
    // TODO: scaleIFrame(); // TODO.
}

function processHashFromUrlParam(hashParam) {
    if (hashParam) {
        if (hashParam.length == 66 && hashParam.substr(0, 2) == '0x') {
            resetBotti();
            verifydiv.scrollIntoView();
            h_1.style.display = "block";
            p_hash.style.display = "block";
            p_hash.innerHTML = 'Hash: <span style="color:#106F6F;">' + hashParam + '</span>';
            processHash(hashParam);
        }
        else {
            textbox.innerHTML = 'Hash parameter: wrong format';
        }
    }
}