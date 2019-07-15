var button = document.getElementById('js_theme_toggle');
var table = document.getElementById('js_switch_test');

button.addEventListener('click', () => {
  let light = 'on-light-background-color';
  let dark = 'on-dark-background-color';

  if ( table.classList.contains(light) ) {
    let a = table.classList.contains(light);
    console.log(a);
    table.classList.remove(light);
    table.classList.add(dark);
  } else {
    table.classList.remove(dark);
    table.classList.add(light);
  }

});
