var modal = document.getElementById('myModal');
var btns = document.getElementsByClassName("myBtn");
var span = document.getElementsByClassName("close")[0];
var issued = document.getElementById('issued');
var pk = document.getElementById('pk');
var check_bc = document.getElementById('check_only_bc');
var check_nonbc = document.getElementById('check_only_nonbc');

check_bc.onclick = function() {
	check_nonbc.checked = false;
}

check_nonbc.onclick = function() {
	check_bc.checked = false;
}

for(var i=0; i<btns.length; i++){
  btns[i].addEventListener("click", (function(i) { 
    return function(){ 
      modal.style.display = "block"; 
      issued.value = btns[i].value;
    }
  })(i))
}

span.onclick = function() {
  modal.style.display = "none";
  pk.value = '';
}

window.onclick = function(event) {
  if (event.target == modal) {
    modal.style.display = "none";
    pk.value = '';
  }
}