// Funcionalidad de búsqueda y filtrado - VERSIÓN CORREGIDA
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM cargado - inicializando scripts maestro...');

    // ===== BÚSQUEDA DE ALUMNOS =====
    const buscarInput = document.getElementById('buscarAlumnos');
    const tablaAlumnos = document.getElementById('tablaAlumnos');
    
    if (tablaAlumnos && buscarInput) {
        console.log('Inicializando búsqueda de alumnos...');
        const filas = tablaAlumnos.querySelectorAll('tbody tr');
        
        buscarInput.addEventListener('input', function() {
            const textoBusqueda = this.value.toLowerCase();
            
            filas.forEach(fila => {
                const textoFila = fila.textContent.toLowerCase();
                
                if (textoFila.includes(textoBusqueda)) {
                    fila.style.display = '';
                } else {
                    fila.style.display = 'none';
                }
            });
        });
    }

    // ===== VALIDACIÓN DE FORMULARIO DE ALUMNOS =====
    const formularioAlumno = document.querySelector('.registro-form');
    if (formularioAlumno) {
        console.log('Inicializando validación de formulario de alumnos...');
        
        formularioAlumno.addEventListener('submit', function(e) {
            const pin = document.getElementById('pin');
            const numeroControl = document.getElementById('numero_control');
            let hayError = false;
            
            // Validar PIN (4 dígitos numéricos)
            if (pin && (pin.value.length !== 4 || !/^\d+$/.test(pin.value))) {
                e.preventDefault();
                alert('El PIN debe tener exactamente 4 dígitos numéricos.');
                pin.focus();
                hayError = true;
            }
            
            // Validar Número de Control (solo números)
            if (numeroControl && !/^\d+$/.test(numeroControl.value)) {
                e.preventDefault();
                alert('El número de control solo debe contener números.');
                if (!hayError) {
                    numeroControl.focus();
                }
                hayError = true;
            }
        });
        
        // Validación en tiempo real para Número de Control
        const numeroControl = document.getElementById('numero_control');
        if (numeroControl) {
            numeroControl.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
            });
            
            numeroControl.addEventListener('keypress', function(e) {
                // Solo permitir teclas numéricas
                if (!/[0-9]/.test(e.key)) {
                    e.preventDefault();
                }
            });
        }
        
        // Validación en tiempo real para PIN
        const pin = document.getElementById('pin');
        if (pin) {
            pin.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
                
                // Limitar a 4 dígitos
                if (this.value.length > 4) {
                    this.value = this.value.slice(0, 4);
                }
            });
            
            pin.addEventListener('keypress', function(e) {
                // Solo permitir teclas numéricas
                if (!/[0-9]/.test(e.key)) {
                    e.preventDefault();
                }
                
                // Limitar a 4 dígitos
                if (this.value.length >= 4) {
                    e.preventDefault();
                }
            });
        }
    }

    // ===== SOLICITUD DE INSUMOS =====
    const selectLaboratorio = document.getElementById('laboratorio');
    const insumosContainer = document.getElementById('insumos-container');
    const listaInsumos = document.getElementById('lista-insumos');
    const mensajeCarga = document.getElementById('mensaje-carga');
    const formSolicitudInsumos = document.getElementById('formSolicitudInsumos');

    // Solo inicializar si estamos en la página de solicitud de insumos
    if (selectLaboratorio) {
        console.log('Inicializando funcionalidad de insumos...');

        // Event listener para cambio de laboratorio
        selectLaboratorio.addEventListener('change', function() {
            const idLaboratorio = this.value;
            
            console.log('Laboratorio seleccionado:', idLaboratorio);
            
            // Validar que sea un número válido
            if (!idLaboratorio || isNaN(idLaboratorio) || idLaboratorio <= 0) {
                console.log('ID de laboratorio inválido');
                if (insumosContainer) insumosContainer.style.display = 'none';
                return;
            }
            
            // Mostrar mensaje de carga
            if (mensajeCarga) mensajeCarga.style.display = 'block';
            if (insumosContainer) insumosContainer.style.display = 'none';
            if (listaInsumos) listaInsumos.innerHTML = '';
            
            // URL integrada en el mismo archivo
            const url = 'GestionMaestro.php?action=obtener_insumos&laboratorio=' + idLaboratorio;
            console.log('Solicitando URL:', url);
            
            fetch(url)
                .then(response => {
                    console.log('Estado de respuesta:', response.status, response.statusText);
                    
                    if (!response.ok) {
                        throw new Error(`Error HTTP: ${response.status} ${response.statusText}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Datos recibidos:', data);
                    if (mensajeCarga) mensajeCarga.style.display = 'none';
                    
                    if (data.error) {
                        // Si hay error en la respuesta
                        if (listaInsumos) {
                            listaInsumos.innerHTML = `
                                <tr>
                                    <td colspan="4" style="text-align: center; color: #e74c3c;">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        <strong>Error del servidor</strong><br>
                                        <small>${data.error}</small>
                                    </td>
                                </tr>`;
                        }
                        if (insumosContainer) insumosContainer.style.display = 'block';
                    } else if (Array.isArray(data) && data.length > 0) {
                        if (listaInsumos) {
                            data.forEach(insumo => {
                                const fila = document.createElement('tr');
                                fila.innerHTML = `
                                    <td>${insumo.NOMBRE}</td>
                                    <td>${insumo.DESCRIPCION || 'N/A'}</td>
                                    <td>
                                        <span class="cantidad-badge ${insumo.CANTIDAD_DIS < 10 ? 'bajo' : 'normal'}">
                                            ${insumo.CANTIDAD_DIS}
                                        </span>
                                    </td>
                                    <td>
                                        <input type="number" 
                                            name="insumos[${insumo.idINSUMOS}]" 
                                            min="0" 
                                            max="${insumo.CANTIDAD_DIS}"
                                            value="0"
                                            class="cantidad-insumo"
                                            style="width: 80px; padding: 5px;"
                                            placeholder="0">
                                        <small style="display: block; color: #666; font-size: 11px;">
                                            Mín: 0, Máx: ${insumo.CANTIDAD_DIS}
                                        </small>
                                    </td>
                                `;
                                listaInsumos.appendChild(fila);
                            });
                        }
                        if (insumosContainer) insumosContainer.style.display = 'block';
                    } else {
                        if (listaInsumos) {
                            listaInsumos.innerHTML = `
                                <tr>
                                    <td colspan="4" style="text-align: center; padding: 20px;">
                                        <i class="fas fa-box-open" style="font-size: 40px; color: #ddd; margin-bottom: 10px;"></i>
                                        <h4>No hay insumos disponibles</h4>
                                        <p>No hay insumos con stock disponible en este laboratorio</p>
                                    </td>
                                </tr>`;
                        }
                        if (insumosContainer) insumosContainer.style.display = 'block';
                    }
                })
                .catch(error => {
                    console.error('Error completo:', error);
                    if (mensajeCarga) mensajeCarga.style.display = 'none';
                    if (listaInsumos) {
                        listaInsumos.innerHTML = `
                            <tr>
                                <td colspan="4" style="text-align: center; color: #e74c3c;">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <strong>Error al cargar los insumos</strong><br>
                                    <small>${error.message}</small>
                                </td>
                            </tr>`;
                    }
                    if (insumosContainer) insumosContainer.style.display = 'block';
                });
        });

        // Validación del formulario de insumos - SOLO SI EXISTE
        if (formSolicitudInsumos) {
            console.log('Inicializando validación de formulario de insumos...');
            formSolicitudInsumos.addEventListener('submit', function(e) {
                const cantidadInputs = document.querySelectorAll('.cantidad-insumo');
                let tieneSolicitudesValidas = false;
                
                console.log('Validando formulario de insumos...');
                
                cantidadInputs.forEach(input => {
                    const cantidad = parseInt(input.value) || 0;
                    const maximo = parseInt(input.max) || 0;
                    
                    // Validar que no exceda el máximo permitido
                    if (cantidad > maximo) {
                        e.preventDefault();
                        alert(`La cantidad solicitada no puede exceder ${maximo}.`);
                        input.focus();
                        return false;
                    }
                    
                    // Validar que al menos un insumo tenga cantidad > 0
                    if (cantidad > 0) {
                        tieneSolicitudesValidas = true;
                    }
                });
                
                if (!tieneSolicitudesValidas) {
                    e.preventDefault();
                    alert('Debe solicitar al menos un insumo con cantidad mayor a 0.');
                    return false;
                }
                
                console.log('Formulario de insumos válido');
            });
        } else {
            console.log('FormSolicitudInsumos no encontrado - probablemente no estamos en la página de insumos');
        }
    } else {
        console.log('Select laboratorio no encontrado - no estamos en la página de insumos');
    }

    // ===== TOGGLE SIDEBAR =====
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    
    if (sidebarToggle && sidebar && mainContent) {
        console.log('Inicializando toggle de sidebar...');
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('sidebar-collapsed');
        });

        // ===== MANEJO RESPONSIVE =====
        function handleResize() {
            if (window.innerWidth < 992) {
                sidebar.classList.add('collapsed');
                mainContent.classList.add('sidebar-collapsed');
            } else {
                sidebar.classList.remove('collapsed');
                mainContent.classList.remove('sidebar-collapsed');
            }
        }
        
        // Initial check on page load
        handleResize();
        
        // Add resize listener
        window.addEventListener('resize', handleResize);
    }

    // ===== FUNCIONALIDADES ESPECÍFICAS DE TRABAJADORES (si existen) =====
    const buscarTrabajadores = document.getElementById('buscarTrabajadores');
    const filtroRol = document.getElementById('filtroRol');
    const tablaTrabajadores = document.getElementById('tablaTrabajadores');
    
    if (tablaTrabajadores) {
        console.log('Inicializando búsqueda de trabajadores...');
        const filas = tablaTrabajadores.querySelectorAll('tbody tr');
        
        function filtrarTabla() {
            const textoBusqueda = buscarTrabajadores.value.toLowerCase();
            const valorRol = filtroRol.value;
            
            filas.forEach(fila => {
                const textoFila = fila.textContent.toLowerCase();
                const rol = fila.cells[5].textContent.toLowerCase();
                
                const coincideBusqueda = textoFila.includes(textoBusqueda);
                const coincideRol = valorRol === '' || rol === valorRol;
                
                if (coincideBusqueda && coincideRol) {
                    fila.style.display = '';
                } else {
                    fila.style.display = 'none';
                }
            });
        }
        
        if (buscarTrabajadores) buscarTrabajadores.addEventListener('input', filtrarTabla);
        if (filtroRol) filtroRol.addEventListener('change', filtrarTabla);
    }

    console.log('Todos los scripts inicializados correctamente');
});

// Validación en tiempo real para campos de solo letras
function configurarValidacionSoloLetras(campo) {
    campo.addEventListener('input', function() {
        this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '');
    });
    
    campo.addEventListener('keypress', function(e) {
        const char = e.key;
        if (!/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]$/.test(char) && 
            !['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab', 'Enter'].includes(e.key)) {
            e.preventDefault();
        }
    });
}

// Validación en tiempo real para teléfono (solo números, máximo 10)
function configurarValidacionTelefono(campo) {
    campo.addEventListener('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
        if (this.value.length > 10) {
            this.value = this.value.slice(0, 10);
        }
    });
    
    campo.addEventListener('keypress', function(e) {
        if (!/[0-9]/.test(e.key) && 
            !['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab', 'Enter'].includes(e.key)) {
            e.preventDefault();
        }
    });
}

// Aplicar validaciones cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    // Aplicar validación de solo letras a nombre, paterno y materno
    const nombre = document.getElementById('nombre');
    const paterno = document.getElementById('paterno');
    const materno = document.getElementById('materno');
    
    if (nombre) configurarValidacionSoloLetras(nombre);
    if (paterno) configurarValidacionSoloLetras(paterno);
    if (materno) configurarValidacionSoloLetras(materno);
    
    // Aplicar validación de teléfono
    const telefono = document.getElementById('telefono');
    if (telefono) configurarValidacionTelefono(telefono);
    
    // Validación de correo electrónico en tiempo real
    const correo = document.getElementById('correo');
    if (correo) {
        correo.addEventListener('blur', function() {
            if (this.value && !this.value.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
                this.style.borderColor = '#e74c3c';
                // Puedes mostrar un mensaje de error aquí si lo deseas
            } else {
                this.style.borderColor = '';
            }
        });
    }
});

// Validación en tiempo real para campos de solo letras con límite de caracteres
function configurarValidacionSoloLetras(campo, maxLength) {
    if (!campo) return;
    
    campo.addEventListener('input', function() {
        this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '');
        if (this.value.length > maxLength) {
            this.value = this.value.slice(0, maxLength);
        }
    });
    
    campo.addEventListener('keypress', function(e) {
        const char = e.key;
        if (!/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]$/.test(char) && 
            !['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab', 'Enter'].includes(e.key)) {
            e.preventDefault();
        }
    });
}

// Validación en tiempo real para correo con límite de caracteres
function configurarValidacionCorreo(campo, maxLength) {
    if (!campo) return;
    
    campo.addEventListener('input', function() {
        if (this.value.length > maxLength) {
            this.value = this.value.slice(0, maxLength);
        }
    });
}

// Aplicar validaciones cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('Aplicando validaciones para gestión de alumnos...');
    
    // Aplicar validación de solo letras a nombre, paterno y materno con límites
    const nombre = document.getElementById('nombre');
    const paterno = document.getElementById('paterno');
    const materno = document.getElementById('materno');
    
    if (nombre) configurarValidacionSoloLetras(nombre, 30);
    if (paterno) configurarValidacionSoloLetras(paterno, 30);
    if (materno) configurarValidacionSoloLetras(materno, 30);
    
    // Aplicar validación de teléfono
    const telefono = document.getElementById('telefono');
    if (telefono) configurarValidacionTelefono(telefono);
    
    // Aplicar validación de número de control
    const numeroControl = document.getElementById('numero_control');
    if (numeroControl) configurarValidacionNumeroControl(numeroControl);
    
    // Aplicar validación de PIN
    const pin = document.getElementById('pin');
    if (pin) configurarValidacionPIN(pin);
    
    // Aplicar validación de correo con límite
    const correo = document.getElementById('correo');
    if (correo) {
        configurarValidacionCorreo(correo, 200);
        
        correo.addEventListener('blur', function() {
            if (this.value && !this.value.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
                this.style.borderColor = '#e74c3c';
            } else {
                this.style.borderColor = '';
            }
        });
    }
    
    console.log('Validaciones aplicadas correctamente');
});