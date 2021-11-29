let modal = null;
let modal_to_bc = null;
let modal_reissue = null;
let modal_dialog = null;
let modal_backdrop = null;
let modal_close_btns = null;
let selected_input_to_bc = null;
let selected_input_reissue = null;
let cert_lists = null;
let selected = new Set();

let certs = [];
let certs_table = null;
let sign_btns = null;
let reissue_btns = null;
let check_all = null;
let check_certs = null;
let bulk_action = null;
let bulk_action_button = null;

let reset_search = null;
let search_input = null;
let filter_input = null;

window.addEventListener('load', init);

function init() {
  modal = document.getElementById('m-element-modal');
  if (modal) {
    modal_to_bc = modal.querySelector('#m-element-modal__to-bc');
    modal_reissue = modal.querySelector('#m-element-modal__reissue');
    modal_dialog = modal.querySelector('.modal-dialog');
    modal_backdrop = document.querySelector('.modal-backdrop');
    modal_close_btns = modal.querySelectorAll(
      '#m-element-modal .close, #m-element-to-bc__cancel, #m-element-reissue__cancel'
    );
    selected_input_to_bc = modal_to_bc.querySelector(
      '#m-element-to-bc__selected'
    );
    selected_input_reissue = modal_reissue.querySelector(
      '#m-element-reissue__selected'
    );
    cert_lists = modal.querySelectorAll('.m-element-modal__selected_certs');
  }

  certs = [];
  certs_table = document.querySelector('.m-element-certs-table');
  if (certs_table) {
    sign_btns = certs_table.querySelectorAll('.m-element-sign-cert');
    reissue_btns = certs_table.querySelectorAll('.m-element-reissue');
    check_all = certs_table.querySelector('#m-element-select-all-certs');
    check_certs = certs_table.querySelectorAll('.m-element-select-cert');
    bulk_action = certs_table.querySelector('#m-element-bulk-actions');
    bulk_action_button = certs_table.querySelector(
      '#m-element-bulk-actions__button'
    );
  }

  reset_search = document.getElementById('id_search_reset');
  search_input = document.getElementById('m-element-search__query');
  filter_input = document.getElementById('m-element-search__filter');

  unselectAll();

  getCerts();

  if (modal_close_btns) {
    modal_close_btns.forEach((close) =>
      close.addEventListener('click', closeModal)
    );
  }

  if (reset_search) {
    reset_search.addEventListener('click', reset);
  }

  if (bulk_action_button && bulk_action) {
    bulk_action_button.addEventListener('click', () => {
      if (selected.size > 0) {
        showModal(bulk_action.value);
      }
    });
  }

  if (check_certs) {
    check_certs.forEach((checkbox) => {
      checkbox.addEventListener('change', () => updateSelected(checkbox));
    });
  }

  if (check_all) {
    check_all.addEventListener('change', function () {
      if (this.checked) {
        selectAll();
      } else {
        unselectAll();
      }
    });
  }

  if (sign_btns) {
    for (var i = 0; i < sign_btns.length; i++) {
      sign_btns[i].addEventListener('click', function () {
        unselectAll();
        selected.add(this.value);
        showModal('toblockchain');
      });
    }
  }

  if (reissue_btns) {
    for (var i = 0; i < reissue_btns.length; i++) {
      reissue_btns[i].addEventListener('click', function () {
        unselectAll();
        selected.add(this.value);
        showModal('reissue');
      });
    }
  }

  window.onclick = function (event) {
    if (event.target === modal) {
      closeModal();
    }
  };
}

function showModal(action) {
  if (!modal || !modal_backdrop || !modal_dialog) {
    return false;
  }
  if (action === 'toblockchain') {
    modal_to_bc.classList.remove('hidden');
    modal_to_bc.classList.add('show');
  } else if (action === 'reissue') {
    modal_reissue.classList.remove('hidden');
    modal_reissue.classList.add('show');
  } else {
    return false;
  }

  setSelected();
  modal.classList.remove('hidden');
  modal.classList.add('show');
  modal_dialog.focus();
  modal_backdrop.classList.remove('hidden');
  modal_backdrop.classList.add('show');
}

function closeModal() {
  if (!modal && !modal_backdrop) {
    return false;
  }
  modal.classList.remove('show');
  modal.classList.add('hidden');
  modal_backdrop.classList.remove('show');
  modal_backdrop.classList.add('hidden');
  modal_reissue.classList.remove('show');
  modal_reissue.classList.add('hidden');
  modal_to_bc.classList.remove('show');
  modal_to_bc.classList.add('hidden');
}

function updateSelected(checkbox) {
  if (checkbox.checked) {
    selected.add(checkbox.value);
  } else {
    selected.delete(checkbox.value);
  }
}

function selectAll() {
  check_certs.forEach((checkbox) => {
    checkbox.checked = true;
    selected.add(checkbox.value);
  });
}

function unselectAll() {
  if (!check_all) {
    return false;
  }

  check_certs.forEach((checkbox) => {
    checkbox.checked = false;
    selected.delete(checkbox.value);
  });
  check_all.checked = false;
}

function reset() {
  if (search_input) {
    search_input.value = '';
  }
  if (filter_input) {
    filter_input.value = '';
  }
  return true;
}

function setSelected() {
  cert_lists.forEach((list) => {
    removeChilds(list);

    selected.forEach((cert) => {
      let cert_item = document.createElement('li');
      cert_item.textContent =
        certs[cert].name +
        ' | ' +
        certs[cert].recipient +
        ' | ' +
        certs[cert].date;
      list.appendChild(cert_item);
    });
  });

  if (selected_input_to_bc) {
    selected_input_to_bc.value = JSON.stringify([...selected]);
  }

  if (selected_input_reissue) {
    selected_input_reissue.value = JSON.stringify([...selected]);
  }
}

function removeChilds(parent) {
  while (parent.lastChild) {
    parent.removeChild(parent.lastChild);
  }
}

function getCerts() {
  if (!certs_table) {
    return false;
  }
  let rows = certs_table.querySelectorAll('tbody > tr');
  rows.forEach((row) => {
    let cert = {
      name: row.querySelector('.c2 a').textContent,
      recipient: row.querySelector('.c3 a').textContent,
      date: row.querySelector('.c4').textContent,
    };

    let id = row.querySelector('.m-element-select-cert').value;
    certs[id] = cert;
  });
}
