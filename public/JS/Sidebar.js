
const btn = document.getElementById('personal-btn');
const submenu = document.getElementById('submenu');
let visible = false;

const recintoBtn = document.getElementById('recinto-btn');
const recintoSubmenu = document.getElementById('recinto-submenu');
let recintoVisible = false;

const rolesBtn = document.getElementById('roles-btn');
const rolesSubmenu = document.getElementById('roles-submenu');
let rolesVisible = false;

// Cerrar otros submenús al abrir uno
function closeAllSubmenus(except) {
  if (except !== 'personal' && visible && submenu) {
    submenu.style.display = 'none';
    visible = false;
  }
  if (except !== 'recinto' && recintoVisible && recintoSubmenu) {
    recintoSubmenu.style.display = 'none';
    recintoVisible = false;
  }
  if (except !== 'roles' && rolesVisible && rolesSubmenu) {
    rolesSubmenu.style.display = 'none';
    rolesVisible = false;
  }
}

btn?.addEventListener('click', (e) => {
  e.stopPropagation();
  closeAllSubmenus('personal');
  visible = !visible;
  submenu.style.display = visible ? 'block' : 'none';
  if (visible) {
    submenu.style.top = `${btn.offsetTop}px`;
  }
});

recintoBtn?.addEventListener('click', (e) => {
  e.stopPropagation();
  closeAllSubmenus('recinto');
  recintoVisible = !recintoVisible;
  recintoSubmenu.style.display = recintoVisible ? 'block' : 'none';
  if (recintoVisible) {
    recintoSubmenu.style.top = `${recintoBtn.offsetTop}px`;
  }
});

rolesBtn?.addEventListener('click', (e) => {
  e.stopPropagation();
  closeAllSubmenus('roles');
  rolesVisible = !rolesVisible;
  rolesSubmenu.style.display = rolesVisible ? 'block' : 'none';
  if (rolesVisible) {
    rolesSubmenu.style.top = `${rolesBtn.offsetTop}px`;
  }
});

document.addEventListener('click', function (event) {
  if (btn && submenu && !btn.contains(event.target) && !submenu.contains(event.target)) {
    submenu.style.display = 'none';
    visible = false;
  }
  if (recintoBtn && recintoSubmenu && !recintoBtn.contains(event.target) && !recintoSubmenu.contains(event.target)) {
    recintoSubmenu.style.display = 'none';
    recintoVisible = false;
  }
  if (rolesBtn && rolesSubmenu && !rolesBtn.contains(event.target) && !rolesSubmenu.contains(event.target)) {
    rolesSubmenu.style.display = 'none';
    rolesVisible = false;
  }
});


// JS para toggle de sidebar
document.getElementById('menu-toggle').addEventListener('click', function () {
  const sidebar = document.getElementById('sidebar');
  sidebar.classList.toggle('d-none');
});


function toggleSidebar() {
  const sidebar = document.getElementById('sidebar');
  sidebar.classList.toggle('show');
}

// Cierra el sidebar al hacer clic fuera
document.addEventListener('click', function (event) {
  const sidebar = document.getElementById('sidebar');
  const hamburger = document.querySelector('.hamburger');
  if (
    window.innerWidth <= 767 &&
    sidebar.classList.contains('show') &&
    !sidebar.contains(event.target) &&
    !hamburger.contains(event.target)
  ) {
    sidebar.classList.remove('show');
  }
});