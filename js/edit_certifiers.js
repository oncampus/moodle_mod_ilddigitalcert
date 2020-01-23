var buttons = document.getElementById('pk_button_p');
var pk_input = document.getElementById('pk_input');
var submit = document.getElementById('chooseusersubmit');

var btn = document.getElementById("pk_button");
var reset_btn = document.getElementById("pk_button_reset");

var submit_btn = document.getElementById("pk_submit");
var submit_reset_btn = document.getElementById("pk_submit_reset");

//var testBtn = document.getElementById("testBtn");

var btns = document.getElementsByClassName("deleteBtn");
var registerBtns = document.getElementsByClassName("registerBtn");
var modal = document.getElementById('myModal');
var modalAdd = document.getElementById('myModalAddCertifier');
var userpref = document.getElementById('userpref');
var userprefAdd = document.getElementById('userprefAdd');

var spans = document.getElementsByClassName("close");

var adr_input = document.getElementById("certifierAddress");
var select = document.getElementById("certifier");

var warning_select_user = document.getElementById("warning_select_user");
var check_generate_pk = document.getElementById("radio_generate_pk");
var radiogroup1 = document.getElementById("radiogroup1");

select.required = true;

radiogroup1.onclick = function() {
    if (check_generate_pk.checked == false) {
        adr_input.disabled = false;
        adr_input.required = true;
    }
    else {
        adr_input.disabled = true;
        adr_input.required = false;
        adr_input.value = "";
    }
}

select.onclick = function() {
    //console.log(select.selectedIndex);
}

btn.onclick = function() {
    if ((adr_input.value != "" || check_generate_pk.checked == true) && select.selectedIndex >= 0) {
        pk_input.required = true;
        pk_input.style.display = "block";
        submit.style.display = "block";
        buttons.style.display = "none";
		warning_select_user.style.display = "none";
    }
	if (select.selectedIndex == -1) {
		// console.log("select user! required!");
		warning_select_user.style.display = "block";
	}
}
//*
reset_btn.onclick = function() {
    adr_input.disabled = true;
    adr_input.required = false;
    adr_input.value = "";
}
//*/
submit_reset_btn.onclick = function() {
    pk_input.required = false;
    pk_input.style.display = "none";
    submit.style.display = "none";
    buttons.style.display = "block";
}

for(var i=0; i<btns.length; i++){
    btns[i].addEventListener("click", (function(i) { 
      return function(){ 
        modal.style.display = "block"; 
        userpref.value = btns[i].value;
      }
    })(i))
  }
  
for(var i=0; i<registerBtns.length; i++){
    registerBtns[i].addEventListener("click", (function(i) { 
      return function(){ 
        modalAdd.style.display = "block"; 
        userprefAdd.value = registerBtns[i].value;
      }
    })(i))
}

for(var i=0; i<spans.length; i++){
	spans[i].addEventListener("click", (function(i) { 
		return function() {
			modal.style.display = "none";
			modalAdd.style.display = "none";
            pk.value = '';
            pkAdd.value = '';
		}
	})(i))
}

// When the user clicks anywhere outside of the modal, close it
window.onclick = function(event) {
    if (event.target == modal) {
        modal.style.display = "none";
        //pk.value = '';
    }
    else if (event.target == modalAdd) {
        modalAdd.style.display = "none";
        //pkAdd.value = '';
    }
}