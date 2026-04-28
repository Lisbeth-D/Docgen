<aside class="admin-sidebar" id="sidebar">

    {{-- BOTÓN TOGGLE --}}
    <button class="toggle-btn" onclick="toggleSidebar()">☰</button>

    <ul>

        {{-- CONVOCATORIA --}}
        <li>
            <a href="{{ route('convocatoria') }}">
                <i data-feather="file-text"></i>
                <span>Convocatoria</span>
            </a>
        </li>

        {{-- OFICIOS --}}
        <li>
            <div class="menu-title" onclick="toggleOficios()">
                <i data-feather="folder"></i>
                <span>Oficios</span>
                <i data-feather="chevron-down" class="chevron"></i>
            </div>

            <ul class="submenu" id="oficiosSubmenu">
                <li>
                    <li>
                        <a href="{{ route('revision.form') }}">
                            <i data-feather="search"></i>
                            Revisión
                        </a>
                    </li>
                </li>
                <li>
                    <a href="{{ route('publicacion.index') }}">
                        <i data-feather="upload"></i>
                        Publicación
                    </a>
                </li>
                <li>
                    <a href="{{ route('designacion.index') }}">
                        <i data-feather="award"></i>
                        Designación
                    </a>
                </li>
                <li>
                    <a href="{{ route('adjudicacion.index') }}">
                        <i data-feather="award"></i>
                        Adjudicación
                    </a>
                </li>
            </ul>
        </li>

        {{-- ACLARACIONES --}}
        <li>
            <div class="menu-title" onclick="toggleAclaraciones()">
                <i data-feather="help-circle"></i>
                <span>Aclaraciones</span>
                <i data-feather="chevron-down" class="chevron"></i>
            </div>

            <ul class="submenu" id="aclaracionesSubmenu">

                {{-- SI APLICA JUNTA --}}
                <li>
                    <div class="menu-title submenu-title" onclick="toggleSiAplica()">
                        <i data-feather="check-square"></i>
                        <span>Si aplica junta</span>
                        <i data-feather="chevron-down" class="chevron"></i>
                    </div>

                    <ul class="submenu nested" id="siAplicaSubmenu">
                        <li>
                            <a href="{{ route('ac.index') }}">
                                <i data-feather="file-text"></i>
                                Acta preguntas
                            </a>
                        </li>
                        <li>
                            <a href="#">
                                <i data-feather="file-tex"></i>
                                Acta de Cierre
                            </a>
                        </li>
                        <li>
                            <a href="#">
                                <i data-feather="file-tex"></i>
                                Acta 
                            </a>
                        </li>
                    </ul>
                </li>

                {{-- NO APLICA JUNTA --}}
                <li>
                    <a href="#">
                        <i data-feather="x-circle"></i>
                        No aplica junta
                    </a>
                </li>

            </ul>
        </li>

        {{-- APERTURA --}}
        <li>
            <a href="#">
                <i data-feather="package"></i>
                <span>Apertura</span>
            </a>
        </li>

        {{-- FALLO --}}
        <li>
            <a href="#">
                <i data-feather="check-circle"></i>
                <span>Fallo</span>
            </a>
        </li>

        {{-- CONTRATOS --}}
        <li>
            <a href="#">
                <i data-feather="file"></i>
                <span>Contratos</span>
            </a>
        </li>

        {{-- MACHOTE --}}
        <li>
            <a href="#">
                <i data-feather="edit-3"></i>
                <span>Generador de machote</span>
            </a>
        </li>

    </ul>
</aside>