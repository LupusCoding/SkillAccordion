/* Toggle accordions */
function ska_acc_toggle(event) {
  var button = event.srcElement;
  if (button.dataset.toggle == 'ska-col') {
    var cid = button.dataset.parent.substring(8);
    var col = document.getElementById(cid);
    var group = col.parentNode.getElementsByClassName('ska-col');
    for (gc=0; gc<group.length; gc++) {
      group[gc].classList.remove('in');
    }
    col.classList.add('in');
    ska_toggle_buttons(button.dataset.parent);
  }
  event.preventDefault();
}
/* Toggle buttons */
function ska_toggle_buttons(button_id) {
  var ska_buttons = document.querySelectorAll('.ska-pan-group a');
  for (bc=0; bc<ska_buttons.length; bc++) {
    if (ska_buttons[bc].dataset.parent == button_id) {
      ska_buttons[bc].parentNode.parentNode.classList.add('active');
    } else {
      ska_buttons[bc].parentNode.parentNode.classList.remove('active');
    }
  }
}
/* Get buttons */
document.addEventListener('DOMContentLoaded', function() {
  var ska_buttons = document.querySelectorAll('.ska-pan-group a');
  for (bc=0; bc<ska_buttons.length; bc++) {
    ska_buttons[bc].addEventListener('click', ska_acc_toggle);
  }
  ska_buttons[0].parentNode.parentNode.classList.add('active');
});
