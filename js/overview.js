var check_bc = document.getElementById('check_only_bc');
var check_nonbc = document.getElementById('check_only_nonbc');

check_bc.onclick = function() {
	check_nonbc.checked = false;
}

check_nonbc.onclick = function() {
	check_bc.checked = false;
}