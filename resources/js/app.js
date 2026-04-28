import './bootstrap';

import Alpine from 'alpinejs';
import feather from 'feather-icons';

window.Alpine = Alpine;

Alpine.start();

// ICONOS
document.addEventListener("DOMContentLoaded", () => {
    feather.replace();

    // =========================
    // SIDEBAR
    // =========================
    window.toggleSidebar = function () {
        document.getElementById("sidebar")?.classList.toggle("collapsed");
        document.querySelector(".admin-content")?.classList.toggle("expanded");
    };

    // =========================
    // RESPONSABLE TÉCNICO
    // =========================
    const selectTecnico = document.getElementById('resp_tecnico');
    const inputCargo = document.getElementById('cargo_tecnico');

    if (selectTecnico && inputCargo) {
        selectTecnico.addEventListener('change', function () {
            const selected = this.options[this.selectedIndex];
            const cargo = selected.getAttribute('data-cargo');

            inputCargo.value = cargo || '';
        });
    }

});

document.getElementById('num_participantes').addEventListener('change', function () {

    let container = document.getElementById('participantes_container');
    container.innerHTML = '';

    for (let i = 0; i < this.value; i++) {

        container.innerHTML += `
            <div class="conv-group">
                <input type="text" name="participantes[${i}][nombre]" placeholder="Empresa ${i+1}" required>

                <select name="participantes[${i}][pregunta]">
                    <option value="SI">Sí presentó</option>
                    <option value="NO">No presentó</option>
                </select>
            </div>
        `;
    }
});