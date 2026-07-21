<aside class="admin-sidebar" id="sidebar">

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
                    <a href="{{ route('revision.form') }}">
                        <i data-feather="search"></i>
                        <span>Revisión</span>
                    </a>
                </li>

                <li>
                    <a href="{{ route('publicacion.index') }}">
                        <i data-feather="upload"></i>
                        <span>Publicación</span>
                    </a>
                </li>

                <li>
                    <a href="{{ route('designacion.index') }}">
                        <i data-feather="award"></i>
                        <span>Designación</span>
                    </a>
                </li>

                <li>
                    <a href="{{ route('adjudicacion.index') }}">
                        <i data-feather="award"></i>
                        <span>Adjudicación</span>
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
                <li>
                    <div class="menu-title submenu-title" onclick="toggleSiAplica()">
                        <i data-feather="check-square"></i>
                        <span>Sí aplica junta</span>
                        <i data-feather="chevron-down" class="chevron"></i>
                    </div>

                    <ul class="submenu nested" id="siAplicaSubmenu">
                        <li>
                            <a href="{{ route('acpregunta.index') }}">
                                <span>Acta de preguntas</span>
                            </a>
                        </li>

                        <li>
                            <a href="{{ route('ac.index') }}">
                                <span>Acta de Junta de Aclaraciones</span>
                            </a>
                        </li>

                        <li>
                            <a href="{{ route('actacierre.index') }}">
                                <span>Acta de Cierre</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <li>
                    <a href="{{ route('noaplica.index') }}">
                        <i data-feather="x-circle"></i>
                        <span>No aplica junta</span>
                    </a>
                </li>
            </ul>
        </li>

        {{-- APERTURA --}}
        <li>
            <a href="{{ route('apertura.index') }}">
                <i data-feather="package"></i>
                <span>Apertura</span>
            </a>
        </li>

        {{-- FALLO --}}
        <li>
            <div class="menu-title" onclick="toggleFallo()">
                <i data-feather="check-circle"></i>
                <span>Fallo</span>
                <i data-feather="chevron-down" class="chevron"></i>
            </div>

            <ul class="submenu" id="falloSubmenu">
                <li>
                    <a href="{{ route('fallo.acta.index') }}">
                        <span>Acta de fallo</span>
                    </a>
                </li>

                <li>
                    <a href="{{ route('dictamen.fallo.index') }}">
                        <span>Dictamen de fallo</span>
                    </a>
                </li>
            </ul>
        </li>

        {{-- REGISTROS --}}
        <li>
            <a href="{{ route('comprador.registros.index') }}">
                <i data-feather="database"></i>
                <span>Registros</span>
            </a>
        </li>

        {{-- MANUAL --}}
        <li>
            <a href="#">
                <i data-feather="edit-3"></i>
                <span>Manual de sistema</span>
            </a>
        </li>

    </ul>

</aside>