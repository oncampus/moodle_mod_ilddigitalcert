let botti_1 = document.getElementById('botti_blockchain_check_1');
let botti_2 = document.getElementById('botti_blockchain_check_2');
let botti_3 = document.getElementById('botti_blockchain_check_3');
let botti_4 = document.getElementById('botti_blockchain_check_4');
let botti_5 = document.getElementById('botti_blockchain_check_5');
let botti_6 = document.getElementById('botti_blockchain_check_6');
let h_1 = document.getElementById('h1');
let p_hash = document.getElementById('p-hash');
let p_1 = document.getElementById('p1');
let p_2 = document.getElementById('p2');
let p_3 = document.getElementById('p3');
let p_4 = document.getElementById('p4');
let p_5 = document.getElementById('p5');
let p_6 = document.getElementById('p6');
let textbox = document.getElementById('textbox');
let assertionPage = document.getElementById('certpageiframe');
let sleepTime = 1000;

let dropZone = document.getElementById('dropzone');
let verifydiv = document.getElementById('verifydiv');

let imgError1 = document.getElementById('img-error-1');
let imgCheckLoad1 = document.getElementById('img-check-load-1');
let imgCheckLoad2 = document.getElementById('img-check-load-2');
let imgCheckLoad3 = document.getElementById('img-check-load-3');
let imgCheckLoad4 = document.getElementById('img-check-load-4');
let imgCheckLoad1Url = imgCheckLoad1.src;
let imgCheckLoad2Url = imgCheckLoad2.src;
let imgCheckLoad3Url = imgCheckLoad3.src;
let imgCheckLoad4Url = imgCheckLoad4.src;

let divCertData = document.getElementById('certdata');
let spanResultHash = document.getElementById('span-result-hash');
let spanResultStart = document.getElementById('span-result-start');
let prespanResultEnd = document.getElementById('prespan-result-end');
let spanResultEnd = document.getElementById('span-result-end');
let spanResultInstitution = document.getElementById('span-result-institution');

let imgLoader = document.getElementById('loader');
let asText = document.getElementById('asText');

let verificationStepUrl = 'return_verificationstep.php';
url = window.location.href;
if (url.includes('course/view.php')) {
    verificationStepUrl = url.substr(0, url.indexOf('course/view.php')) + 'mod/ilddigitalcert/return_verificationstep.php';
}
else {
    verificationStepUrl = url.substr(0, url.indexOf('mod/ilddigitalcert')) + 'mod/ilddigitalcert/return_verificationstep.php';
}

let verificationMethod = 'hash';

function getUrlParameterByName(name, url) {
    if (!url) {
        url = window.location.href;
    }
    name = name.replace(/[\[\]]/g, "\\$&");
    let regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"), results = regex.exec(url);
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

async function postFormData(url, formdata, jsonResponse = false) {
    const response = await fetch(url, {
        method: 'POST',
        body: formdata
    }).catch((error) => {
        console.error('Error:', error);
    });
    if (jsonResponse) {
        return response.json();
    }
    return response.text();
}

async function dropZoneChange(e) {
    console.log('dropZoneChange');
    resetBotti();
    verifydiv.scrollIntoView();
    let file = asText.files[0];
    let meta = '';
    botti_1.style.display = "block";
    h_1.style.display = "block";
    h_1.innerHTML += ' ' + file.name;
    p_1.style.display = "block";
    if (file.type == 'application/pdf') { // PDF!
        let formData = new FormData();
        formData.append('action', 'pdf');
        formData.append('file', file, file.name);
        console.log('verificationStepUrl: ' + verificationStepUrl);
        meta = await postFormData(verificationStepUrl, formData);
        console.log(meta);
        await sleep(sleepTime);
        processMeta(meta);
    }
    else if (file.type == 'application/json' || file.name.match('.bcrt')) { // JSON!
        let reader = new FileReader();

        reader.onload = async function (e) {
            meta = reader.result;
            await sleep(sleepTime);
            processMeta(meta);
        }
        reader.readAsText(file);
    }
    else if (file.type == 'application/xml' || file.name.match('.xml')) { // XML!
        let reader = new FileReader();

        reader.onload = async function (e) {
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

    let result = 'error';
    let gewaehlteDateien = evt.dataTransfer.files; // FileList Objekt.

    if (gewaehlteDateien.length == 0) {
        console.log('Error: no file!');
        await sleep(sleepTime);
        p_6.style.display = "block";
        activateDropZone();
        return;
    }

    h_1.innerHTML += ' ' + gewaehlteDateien[0].name;

    let formData = new FormData();
    if (gewaehlteDateien[0].type == 'application/pdf') { // PDF!
        formData.append('action', 'pdf');
        formData.append('file', gewaehlteDateien[0], gewaehlteDateien[0].name);
        verificationMethod = 'pdf';
    } else if (gewaehlteDateien[0].type == 'application/json' || gewaehlteDateien[0].name.match('.bcrt')) { // JSON!
        formData.append('action', 'validateJSON');
        formData.append('file', gewaehlteDateien[0], gewaehlteDateien[0].name);
        verificationMethod = 'json';
    } else if (gewaehlteDateien[0].type == 'application/xml' || gewaehlteDateien[0].name.match('.xml')) { // XML!
        formData.append('action', 'validateEDCI');
        formData.append('file', gewaehlteDateien[0], gewaehlteDateien[0].name);
        verificationMethod = 'xml';
    }

    result = await postFormData(verificationStepUrl, formData);
    console.log(result);

    await sleep(sleepTime);
    processMeta(result);
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

async function showCert(meta) {
    console.log(meta);
    console.log(meta['extensions:assertionpageB4E']);
    base64String = meta['extensions:assertionpageB4E'].assertionpage;

    result = await postData(verificationStepUrl, {
        action: 'baseString',
        base64String: base64String,
    });
    console.log(result);
    assertionPage.style.display = "block";
    window.frames[0].document.body.innerHTML = result;
    scaleIFrame();
    verifydiv.scrollIntoView();
    heightPx = assertionPage.contentWindow.document.body.scrollHeight + 80;
    assertionPage.style.height = heightPx + 'px';
}

async function processHash(hash, meta = '') {
    let result = '';
    botti_2.style.display = "none";
    botti_3.style.display = "block";
    p_3.style.display = "block";
    // Search hash in blockchain.
    cert = await postData(verificationStepUrl, {
        action: 'hash',
        hash: hash,
        verificationMethod: verificationMethod,
    }, true);
    console.log(cert);
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
    } else {
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
        institution = await postData(verificationStepUrl, {
            action: 'institution_profile',
            institution_profile: cert.institutionProfile,
            meta: meta,
        }, true);
        console.log(institution);
        if (institution) {
            spanResultInstitution.innerHTML = '<a href="' + institution.url + '"><img title="' + institution.name + '" alt="' + institution.description + '" height="50px" src="' + institution.image + '"/></a>';
        }
        divCertData.scrollIntoView();
        // TODO onhold, expired.
        await sleep(sleepTime);
        if (meta != '') { // If metadata available, show certificate.
            showCert(JSON.parse(meta));
        } else { // Look for cert in DB.
            result = await postData(verificationStepUrl, {
                action: 'cert',
                hash: hash,
            }, true);
            console.log(result);

            if (result) {
                console.log('found metadata');
                if (!institution) {
                    // Get institution from Metadata.
                    spanResultInstitution.innerHTML = '<a href="' + result.badge.issuer.url + '"><img title="' + result.badge.issuer.name + '" alt="' + result.badge.issuer.description + '" height="50px" src="' + result.badge.issuer.image + '"/></a>';
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
    hash = await postData(verificationStepUrl, {
        action: 'meta',
        meta: meta,
    });
    await sleep(sleepTime);
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

window.onresize = function () {
    // TODO: scaleIFrame(); // TODO.
}

function processHashFromUrlParam(hashParam) {
    if (hashParam) {
        if (hashParam.length == 66 && hashParam.substr(0, 2) == '0x') {
            resetBotti();
            verifydiv.scrollIntoView();
            h_1.style.display = "block";
            p_hash.style.display = "block";
            p_hash.innerHTML = 'Hash: <span style="color:#106F6F;">' + hashParam + '</span>'; // TODO Add missing lang string.
            verificationMethod = 'hash';
            processHash(hashParam);
        }
        else {
            textbox.innerHTML = 'Hash parameter: wrong format'; // TODO Add missing lang string.
        }
    }
}