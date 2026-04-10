import './bootstrap';

import Alpine from 'alpinejs';
import feather from 'feather-icons';

window.Alpine = Alpine;

Alpine.start();

document.addEventListener("DOMContentLoaded", () => {
    feather.replace();
});

window.toggleSidebar = function () {
    document.getElementById("sidebar").classList.toggle("collapsed");
    document.querySelector(".admin-content").classList.toggle("expanded");
};