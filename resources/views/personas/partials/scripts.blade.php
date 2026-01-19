<script>
    // --- 1. Funciones Globales de UI ---
    window.openModal = function(id) {
        const el = document.getElementById(id);
        if (el) {
            el.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        } else {
            console.error('Modal no encontrado:', id);
        }
    };

    window.closeModal = function(id) {
        const el = document.getElementById(id);
        if (el) {
            el.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }
    };

    // --- 2. Logica del Modulo ---
    (function() {
        console.log('Modulo Personas: Iniciado');

        const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = csrfTokenMeta ? csrfTokenMeta.getAttribute('content') : '';

        function getMessage(result, fallback) {
            if (result && result.message) return result.message;
            if (result && result.errors) {
                const firstKey = Object.keys(result.errors)[0];
                if (firstKey && result.errors[firstKey] && result.errors[firstKey][0]) {
                    return result.errors[firstKey][0];
                }
            }
            return fallback;
        }

        // A. Logica de Creacion (AJAX)
        const createForm = document.getElementById('create-form');
        if (createForm) {
            createForm.addEventListener('submit', async (e) => {
                e.preventDefault();

                const data = {
                    tipo_documento: document.getElementById('new-tipo_documento').value,
                    numero_documento: document.getElementById('new-numero_documento').value,
                    nombres: document.getElementById('new-nombres').value,
                    apellido_paterno: document.getElementById('new-apellido_paterno').value,
                    apellido_materno: document.getElementById('new-apellido_materno').value,
                    genero: document.getElementById('new-genero').value,
                    fecha_nacimiento: document.getElementById('new-fecha_nacimiento').value,
                    pais: document.getElementById('new-pais').value,
                    departamento: document.getElementById('new-departamento').value,
                    provincia: document.getElementById('new-provincia').value,
                    distrito: document.getElementById('new-distrito').value,
                    direccion: document.getElementById('new-direccion').value,
                    numero_telefonico: document.getElementById('new-numero_telefonico').value,
                    correo_electronico_personal: document.getElementById('new-correo_electronico_personal').value,
                    correo_electronico_corporativo: document.getElementById('new-correo_electronico_corporativo').value,
                };

                try {
                    const response = await fetch('/personas', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(data)
                    });

                    const result = await response.json().catch(() => ({}));
                    if (response.ok) {
                        alert(getMessage(result, 'Persona registrada correctamente'));
                        window.location.reload();
                    } else {
                        alert(getMessage(result, 'Error: Verifique los datos'));
                    }
                } catch (error) {
                    console.error(error);
                    alert('Error de conexion');
                }
            });
        }

        // B. Logica de Edicion (AJAX)
        const btnSave = document.getElementById('btn-save-persona');
        if (btnSave) {
            btnSave.addEventListener('click', async () => {
                const id = document.getElementById('edit-id') ? document.getElementById('edit-id').value : '';
                if (!id) { alert('Error: ID no encontrado.'); return; }

                const data = {
                    tipo_documento: document.getElementById('edit-tdoc').value,
                    numero_documento: document.getElementById('edit-doc').value,
                    nombres: document.getElementById('edit-nombres').value,
                    apellido_paterno: document.getElementById('edit-paterno').value,
                    apellido_materno: document.getElementById('edit-materno').value,
                    fecha_nacimiento: document.getElementById('edit-nac').value,
                    genero: document.getElementById('edit-genero').value,
                    pais: document.getElementById('edit-pais').value,
                    departamento: document.getElementById('edit-departamento').value,
                    provincia: document.getElementById('edit-provincia').value,
                    distrito: document.getElementById('edit-distrito').value,
                    numero_telefonico: document.getElementById('edit-telefono').value,
                    correo_electronico_personal: document.getElementById('edit-correo-pers').value,
                    correo_electronico_corporativo: document.getElementById('edit-correo-corp').value,
                    direccion: document.getElementById('edit-direccion').value,
                };

                try {
                    btnSave.disabled = true;
                    btnSave.innerText = 'Guardando...';

                    const response = await fetch(`/personas/${id}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(data)
                    });

                    const result = await response.json().catch(() => ({}));
                    if (response.ok) {
                        alert(getMessage(result, 'Guardado exitosamente'));
                        window.location.reload();
                    } else {
                        alert(getMessage(result, 'Error: Verifique los datos'));
                    }
                } catch (error) {
                    console.error(error);
                    alert('Error de conexion');
                } finally {
                    btnSave.disabled = false;
                    btnSave.innerText = 'Guardar Cambios';
                }
            });
        }

        // C. Event Delegation (Botones Tabla)
        document.addEventListener('click', function(e) {
            // Boton Editar
            const btnEdit = e.target.closest('.btn-edit');
            if (btnEdit) {
                const data = btnEdit.closest('tr').dataset;
                document.getElementById('edit-id').value = data.id || '';
                setVal('edit-doc', data.doc);
                setVal('edit-tdoc', data.tdoc || 'DNI');
                setVal('edit-nombres', data.nombres);
                setVal('edit-paterno', data.paterno);
                setVal('edit-materno', data.materno);
                setVal('edit-nac', data.nac);
                setVal('edit-genero', data.genero || '1');
                setVal('edit-telefono', data.telefono);
                setVal('edit-correo-pers', data.correoPers);
                setVal('edit-correo-corp', data.correoCorp);
                setVal('edit-direccion', data.direccion);
                setCascadeValues(editCascade, data.pais, data.departamento, data.provincia, data.distrito);

                openModal('edit-modal');
            }

            // Boton Ver
            const btnView = e.target.closest('.btn-view');
            if (btnView) {
                const data = btnView.closest('tr').dataset;
                setVal('view-doc', data.doc);
                setVal('view-tdoc', data.tdoc);
                setVal('view-nombres', data.nombres);
                setVal('view-paterno', data.paterno);
                setVal('view-materno', data.materno);
                setVal('view-nac', data.nac);
                setVal('view-genero', (data.genero == '1' ? 'Masculino' : (data.genero == '2' ? 'Femenino' : 'Otro')));
                setVal('view-telefono', data.telefono);
                setVal('view-correo-pers', data.correoPers);
                setVal('view-correo-corp', data.correoCorp);
                setVal('view-direccion', data.direccion);
                setCascadeValues(viewCascade, data.pais, data.departamento, data.provincia, data.distrito);

                openModal('view-modal');
            }

            // Boton Eliminar
            if (e.target.closest('.btn-delete')) {
                if (confirm('¿Estas seguro de eliminar este registro?')) {
                    alert('Funcionalidad pendiente.');
                }
            }
        });

        // Helper interno
        function setVal(id, val) {
            const el = document.getElementById(id);
            if (el) el.value = val || '';
        }

        // D. Buscador (Debounce)
        const nameInput = document.getElementById('server-search-name');
        const docInput = document.getElementById('server-search-doc');
        let t = null;

        function search() {
            clearTimeout(t);
            t = setTimeout(() => {
                const url = new URL(window.location.href);

                if (nameInput && nameInput.value) url.searchParams.set('search_name', nameInput.value);
                else url.searchParams.delete('search_name');

                if (docInput && docInput.value) url.searchParams.set('search_doc', docInput.value);
                else url.searchParams.delete('search_doc');

                url.searchParams.delete('page');
                window.location.href = url.toString();
            }, 1100);
        }

        if (nameInput) nameInput.addEventListener('input', search);
        if (docInput) docInput.addEventListener('input', search);

        // E. Dependencia Pais -> Departamento -> Provincia -> Distrito (Modales)
        function bindCascade(prefix) {
            const paisSelect = document.getElementById(`${prefix}-pais`);
            const departamentoSelect = document.getElementById(`${prefix}-departamento`);
            const provinciaSelect = document.getElementById(`${prefix}-provincia`);
            const distritoSelect = document.getElementById(`${prefix}-distrito`);

            if (!paisSelect || !departamentoSelect || !provinciaSelect || !distritoSelect) {
                return null;
            }

            const deptOptions = Array.from(departamentoSelect.options);
            const provOptions = Array.from(provinciaSelect.options);
            const distOptions = Array.from(distritoSelect.options);

            function rebuildOptions(select, options) {
                select.innerHTML = '';
                const fragment = document.createDocumentFragment();
                options.forEach((opt) => fragment.appendChild(opt));
                select.appendChild(fragment);
            }

            function selectValueOrText(select, value) {
                if (!value) {
                    select.value = '';
                    return;
                }

                const valueStr = String(value);
                const direct = Array.from(select.options).find((opt) => opt.value === valueStr);
                if (direct) {
                    select.value = valueStr;
                    return;
                }

                const target = valueStr.trim().toLowerCase();
                const byText = Array.from(select.options).find((opt) => {
                    const text = (opt.text || '').trim().toLowerCase();
                    return text === target || text.startsWith(target + ' ') || text.startsWith(target + ' (');
                });
                if (byText) {
                    select.value = byText.value;
                } else {
                    select.value = '';
                }
            }

            function filterDepartamentos() {
                const paisId = paisSelect.value;
                const filtered = deptOptions.filter((opt) => {
                    return !opt.value || opt.dataset.pais === paisId;
                });
                rebuildOptions(departamentoSelect, filtered);
                departamentoSelect.value = '';
                filterProvincias();
            }

            function filterProvincias() {
                const departamentoId = departamentoSelect.value;
                const filtered = provOptions.filter((opt) => {
                    return !opt.value || opt.dataset.departamento === departamentoId;
                });
                rebuildOptions(provinciaSelect, filtered);
                provinciaSelect.value = '';
                filterDistritos();
            }

            function filterDistritos() {
                const provinciaId = provinciaSelect.value;
                const filtered = distOptions.filter((opt) => {
                    return !opt.value || opt.dataset.provincia === provinciaId;
                });
                rebuildOptions(distritoSelect, filtered);
                distritoSelect.value = '';
            }

            function setValues(paisId, departamentoId, provinciaId, distritoId) {
                selectValueOrText(paisSelect, paisId);
                filterDepartamentos();
                selectValueOrText(departamentoSelect, departamentoId);
                filterProvincias();
                selectValueOrText(provinciaSelect, provinciaId);
                filterDistritos();
                selectValueOrText(distritoSelect, distritoId);
            }

            paisSelect.addEventListener('change', filterDepartamentos);
            departamentoSelect.addEventListener('change', filterProvincias);
            provinciaSelect.addEventListener('change', filterDistritos);

            return { setValues };
        }

        function setCascadeValues(cascade, paisId, departamentoId, provinciaId, distritoId) {
            if (cascade) {
                cascade.setValues(paisId, departamentoId, provinciaId, distritoId);
            }
        }

        const createCascade = bindCascade('new');
        const editCascade = bindCascade('edit');
        const viewCascade = bindCascade('view');

        if (createCascade) {
            createCascade.setValues('', '', '', '');
        }

    })();
</script>
