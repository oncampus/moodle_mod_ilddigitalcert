let modal = null;
let modal_to_bc = null;
let modal_reissue = null;
let modal_revoke = null;
let modal_dialog = null;
let modal_backdrop = null;
let modal_close_btns = null;
let selected_input_to_bc = null;
let selected_input_reissue = null;
let selected_input_revoke = null;
let cert_lists = null;
let selected = new Set();

let certs = [];
let certs_table = null;
let action_btns = null;
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
    modal_revoke = modal.querySelector('#m-element-modal__revoke');
    modal_dialog = modal.querySelector('.modal-dialog');
    modal_backdrop = document.querySelector('.modal-backdrop');
    modal_close_btns = modal.querySelectorAll(
      '#m-element-modal .close, #m-element-to-bc__cancel, #m-element-reissue__cancel, #m-element-revoke__cancel'
    );
    selected_input_to_bc = modal_to_bc.querySelector(
      '#m-element-to-bc__selected'
    );
    selected_input_reissue = modal_reissue.querySelector(
      '#m-element-reissue__selected'
    );
    selected_input_revoke = modal_revoke.querySelector(
      '#m-element-revoke__selected'
    );
    cert_lists = modal.querySelectorAll('.m-element-modal__selected_certs');
  }

  certs = [];
  certs_table = document.querySelector('.m-element-certs-table');
  if (certs_table) {
    action_btns = certs_table.querySelectorAll('.m-element-action');
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

  if (modal_close_btns) {
    modal_close_btns.forEach((close) =>
      close.addEventListener('click', closeModal)
    );
  }

  if (reset_search) {
    reset_search.addEventListener('click', reset);
  }

  if (bulk_action_button && bulk_action) {
    
    if (check_certs) {
      bulk_action.addEventListener('change', () => {
        updateCheckboxState();
      });
    }

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

  if (action_btns) {
    for (var i = 0; i < action_btns.length; i++) {
      action_btns[i].addEventListener('click', function () {
        let action = this.getAttribute('action')
        bulk_action.value = action;
        updateCheckboxState();
        unselectAll();
        let row = this.parentNode.parentNode.parentNode;
        row.querySelector('.m-element-select-cert').checked = true;
        selected.add(this.value);
        showModal(action);
      });
    }
  }

  window.onclick = function (event) {
    if (event.target === modal) {
      closeModal();
    }
  };

  unselectAll();

  getCerts();

  updateCheckboxState();
}

function updateCheckboxState() {
  if(!check_certs || !bulk_action) return;
  
  let rows = certs_table.querySelectorAll('tbody > tr:not(.emptyrow)');
  rows.forEach((row) => {
    let checkbox = row.querySelector('.m-element-select-cert');
    let registered = row.querySelector('.col-status img')?.getAttribute('value');
    checkbox.disabled = (bulk_action.value === '' ? false : bulk_action.value === 'revoke' ? !registered : registered);
    updateSelected(checkbox);
  });


    unselectAll();
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
  } else if (action === 'revoke') {
    modal_revoke.classList.remove('hidden');
    modal_revoke.classList.add('show');
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
  modal_revoke.classList.remove('show');
  modal_revoke.classList.add('hidden');
  modal_to_bc.classList.remove('show');
  modal_to_bc.classList.add('hidden');
}

function updateSelected(checkbox) {
  if (!checkbox.checked || checkbox.disabled) {
    selected.delete(checkbox.value);
  } else {
    selected.add(checkbox.value);
  }
}

function selectAll() {
  check_certs.forEach((checkbox) => {
    if(checkbox.disabled === false) {
      checkbox.checked = true;
      selected.add(checkbox.value);
    }
  });
  check_all.checked = true;
}

function unselectAll() {
  check_certs.forEach((checkbox) => {
    checkbox.checked = false;
  });
  selected.clear();
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
      let text = certs[cert].name;
      if(certs[cert].recipient) {
        text += ' | ' + certs[cert].recipient;
      }
      if(certs[cert].course) {
        text += ' | ' + certs[cert].course;
      }
      text += ' | ' + certs[cert].date;
        
      cert_item.textContent = text;
      list.appendChild(cert_item);
    });
  });

  let selected_json = JSON.stringify([...selected])

  if (selected_input_to_bc) {
    selected_input_to_bc.value = selected_json;
  }

  if (selected_input_reissue) {
    selected_input_reissue.value = selected_json;
  }

  if (selected_input_revoke) {
    selected_input_revoke.value = selected_json;
  }
}

function removeChilds(parent) {
  while (parent.lastChild) {
    parent.removeChild(parent.lastChild);
  }
}

function getCerts() {
  if (!certs_table) return false;
  if(!certs_table.querySelector('.col-select')) return false;

  let rows = certs_table.querySelectorAll('tbody > tr:not(.emptyrow)');
  rows.forEach((row) => {
    let id = row.querySelector('.m-element-select-cert')?.value;
    if(!id) return;
    let cert = {
      name: row.querySelector('.col-title a')?.textContent,
      recipient: row.querySelector('.col-recipient a')?.textContent,
      course: row.querySelector('.col-course a')?.textContent,
      date: row.querySelector('.col-startdate')?.textContent,
    };

    certs[id] = cert;
    }
  );
}
