// scriptRegistroTrabajador.js
document.addEventListener('DOMContentLoaded', function() {
    console.log('JavaScript cargado correctamente');
    
    // Obtener la página actual
    const currentPage = window.location.href;
    console.log('Página actual:', currentPage);
    
    // ========== TRABAJADORES ==========
    if (currentPage.includes('pagina=gestion') || currentPage.includes('GestionTrabajador.php')) {
        configurarTrabajadores();
    }
    
    // ========== LABORATORIOS ==========
    if (currentPage.includes('pagina=laboratorios')) {
        configurarLaboratorios();
    }
    
    // ========== INSUMOS ==========
    if (currentPage.includes('pagina=insumos')) {
        configurarInsumos();
    }
    
    // ========== REPORTES ==========
    if (currentPage.includes('pagina=reportes')) {
        configurarReportes();
    }
    
    // ========== FUNCIONALIDADES GLOBALES ==========
    configurarContadoresTextarea();
    configurarGeneradorCodigoBarras();
    
    console.log('Configuración completada para página:', currentPage);
});

// ========== CONFIGURACIÓN DE TRABAJADORES ==========
function configurarTrabajadores() {
    console.log('Configurando funcionalidades para trabajadores');
    
    // Búsqueda para trabajadores
    const buscarTrabajadores = document.getElementById('buscarTrabajadores');
    if (buscarTrabajadores) {
        console.log('Configurando búsqueda de trabajadores');
        buscarTrabajadores.addEventListener('input', function() {
            filtrarTabla('tablaTrabajadores', this.value);
        });
    }

    // Filtro por rol para trabajadores
    const filtroRol = document.getElementById('filtroRol');
    if (filtroRol) {
        filtroRol.addEventListener('change', function() {
            const valorRol = this.value.toLowerCase();
            const tabla = document.getElementById('tablaTrabajadores');
            if (!tabla) return;
            
            const filas = tabla.querySelectorAll('tbody tr');
            
            filas.forEach(fila => {
                const rol = fila.cells[4] ? fila.cells[4].textContent.toLowerCase() : '';
                
                if (valorRol === '' || rol.includes(valorRol)) {
                    fila.style.display = '';
                } else {
                    fila.style.display = 'none';
                }
            });
        });
    }

    // Validaciones para formulario de trabajadores
    configurarValidacionesTrabajadores();
}

function configurarValidacionesTrabajadores() {
    console.log('Configurando validaciones para trabajadores');
    
    // Validación para nombre
    const nombreTrabajador = document.getElementById('nombre');
    if (nombreTrabajador) {
        configurarValidacionSoloLetras(nombreTrabajador);
    }

    // Validación para apellido paterno
    const paternoTrabajador = document.getElementById('paterno');
    if (paternoTrabajador) {
        configurarValidacionSoloLetras(paternoTrabajador);
    }

    // Validación para apellido materno
    const maternoTrabajador = document.getElementById('materno');
    if (maternoTrabajador) {
        configurarValidacionSoloLetras(maternoTrabajador);
    }

    // Validación para número de control (solo números, máximo 8)
    const numeroControl = document.getElementById('numero_control');
    if (numeroControl) {
        configurarValidacionSoloNumeros(numeroControl, 8);
    }

    // Validación para PIN (solo números, máximo 4)
    const pinTrabajador = document.getElementById('pin');
    if (pinTrabajador) {
        configurarValidacionSoloNumeros(pinTrabajador, 4);
    }

    // Validación para teléfono (solo números, máximo 10)
    const telefonoTrabajador = document.getElementById('telefono');
    if (telefonoTrabajador) {
        configurarValidacionSoloNumeros(telefonoTrabajador, 10);
    }
}

// ========== CONFIGURACIÓN DE LABORATORIOS ==========
function configurarLaboratorios() {
    console.log('Configurando funcionalidades para laboratorios');
    
    // Búsqueda para laboratorios
    const buscarLaboratorios = document.getElementById('buscarLaboratorios');
    if (buscarLaboratorios) {
        console.log('Configurando búsqueda de laboratorios');
        buscarLaboratorios.addEventListener('input', function() {
            filtrarTabla('tablaLaboratorios', this.value);
        });
    }

    // Validación para nombre del laboratorio (solo letras y espacios)
    const nombreLab = document.getElementById('nombre_lab');
    if (nombreLab) {
        console.log('Configurando validación para nombre_lab');
        configurarValidacionSoloLetras(nombreLab, 50);
    }

    // Validación para ubicación del laboratorio (letras y números)
    const ubicacionLab = document.getElementById('ubicacion');
    if (ubicacionLab) {
        console.log('Configurando validación para ubicacion');
        configurarValidacionLetrasNumeros(ubicacionLab, 30);
    }
}

// ========== CONFIGURACIÓN DE INSUMOS ==========
function configurarInsumos() {
    console.log('Configurando funcionalidades para insumos');
    
    // Búsqueda y filtrado para insumos
    const buscarInsumos = document.getElementById('buscarInsumos');
    const filtroLaboratorio = document.getElementById('filtroLaboratorio');
    
    function filtrarInsumos() {
        const searchTerm = buscarInsumos ? buscarInsumos.value.toLowerCase() : '';
        const labFilter = filtroLaboratorio ? filtroLaboratorio.value.toLowerCase() : '';
        const table = document.getElementById('tablaInsumos');
        
        if (!table) return;
        
        const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
        
        for (let row of rows) {
            const cells = row.getElementsByTagName('td');
            let foundSearch = searchTerm === '';
            let foundLab = labFilter === '';
            
            // Búsqueda general
            if (searchTerm !== '') {
                for (let cell of cells) {
                    if (cell.textContent.toLowerCase().includes(searchTerm)) {
                        foundSearch = true;
                        break;
                    }
                }
            }
            
            // Filtro por laboratorio
            if (labFilter !== '' && cells[4]) {
                const labCell = cells[4].textContent.toLowerCase();
                foundLab = labCell.includes(labFilter);
            }
            
            row.style.display = (foundSearch && foundLab) ? '' : 'none';
        }
    }
    
    if (buscarInsumos) {
        console.log('Configurando búsqueda de insumos');
        buscarInsumos.addEventListener('input', filtrarInsumos);
    }
    if (filtroLaboratorio) {
        filtroLaboratorio.addEventListener('change', filtrarInsumos);
    }

    // Validación para nombre del insumo (solo letras y espacios)
    const nombreInsumo = document.getElementById('nombre_insumo');
    if (nombreInsumo) {
        console.log('Configurando validación para nombre_insumo');
        configurarValidacionSoloLetras(nombreInsumo, 30);
    }

    // Validación para código de barras (solo números)
    const codigoBarras = document.getElementById('codigo_barras');
    if (codigoBarras) {
        console.log('Configurando validación para codigo_barras');
        configurarValidacionSoloNumeros(codigoBarras, 50);
    }

    // Validación para cantidad (solo números)
    const cantidadInsumo = document.getElementById('cantidad');
    if (cantidadInsumo) {
        console.log('Configurando validación para cantidad');
        configurarValidacionSoloNumeros(cantidadInsumo, 3);
        
        // Validación adicional para rango 0-999
        cantidadInsumo.addEventListener('input', function() {
            if (parseInt(this.value) > 999) {
                this.value = '999';
            }
        });
    }
}

// ========== CONFIGURACIÓN DE REPORTES ==========
function configurarReportes() {
    console.log('Configurando funcionalidades para reportes');
    
    // Manejar el campo "Otra" acción
    const accionSelect = document.getElementById('accion');
    const otraAccionContainer = document.getElementById('otra_accion_container');
    
    if (accionSelect && otraAccionContainer) {
        accionSelect.addEventListener('change', function() {
            if (this.value === 'Otra') {
                otraAccionContainer.style.display = 'block';
                document.getElementById('otra_accion').required = true;
            } else {
                otraAccionContainer.style.display = 'none';
                document.getElementById('otra_accion').required = false;
                document.getElementById('otra_accion').value = '';
            }
        });
    }

    // Validación para campos de texto (solo letras y espacios)
    const camposTexto = ['reporta', 'responsable'];
    
    camposTexto.forEach(campoId => {
        const campo = document.getElementById(campoId);
        if (campo) {
            configurarValidacionSoloLetras(campo);
        }
    });

    // Búsqueda para reportes
    const buscarReportes = document.getElementById('buscarReportes');
    if (buscarReportes) {
        console.log('Configurando búsqueda de reportes');
        buscarReportes.addEventListener('input', function() {
            filtrarTabla('tablaReportes', this.value);
        });
    }

    // Filtro por estatus para reportes
    const filtroEstatus = document.getElementById('filtroEstatus');
    if (filtroEstatus) {
        filtroEstatus.addEventListener('change', function() {
            filtrarTablaPorEstatus('tablaReportes', this.value);
        });
    }

    // Establecer hora actual por defecto en el campo de hora del incidente
    const horaIncidente = document.getElementById('hora_incidente');
    if (horaIncidente) {
        const ahora = new Date();
        const hora = ahora.getHours().toString().padStart(2, '0');
        const minutos = ahora.getMinutes().toString().padStart(2, '0');
        horaIncidente.value = `${hora}:${minutos}`;
    }
}

// ========== FUNCIONES DE VALIDACIÓN REUTILIZABLES ==========
function configurarValidacionSoloLetras(campo, maxLength = null) {
    campo.addEventListener('input', function() {
        this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '');
        if (maxLength && this.value.length > maxLength) {
            this.value = this.value.slice(0, maxLength);
        }
    });

    campo.addEventListener('keypress', function(e) {
        const char = e.key;
        if (!/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]$/.test(char) && 
            !['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab', 'Enter', ' '].includes(e.key)) {
            e.preventDefault();
        }
    });

    campo.addEventListener('paste', function(e) {
        e.preventDefault();
        const text = (e.clipboardData || window.clipboardData).getData('text');
        const cleanedText = text.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '');
        if (maxLength) {
            document.execCommand('insertText', false, cleanedText.slice(0, maxLength));
        } else {
            document.execCommand('insertText', false, cleanedText);
        }
    });
}

function configurarValidacionSoloNumeros(campo, maxLength = null) {
    campo.addEventListener('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
        if (maxLength && this.value.length > maxLength) {
            this.value = this.value.slice(0, maxLength);
        }
    });

    campo.addEventListener('keypress', function(e) {
        const char = e.key;
        if (!/^[0-9]$/.test(char) && 
            !['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab', 'Enter'].includes(e.key)) {
            e.preventDefault();
        }
    });

    campo.addEventListener('paste', function(e) {
        e.preventDefault();
        const text = (e.clipboardData || window.clipboardData).getData('text');
        const cleanedText = text.replace(/[^0-9]/g, '');
        if (maxLength) {
            document.execCommand('insertText', false, cleanedText.slice(0, maxLength));
        } else {
            document.execCommand('insertText', false, cleanedText);
        }
    });
}

function configurarValidacionLetrasNumeros(campo, maxLength = null) {
    campo.addEventListener('input', function() {
        this.value = this.value.replace(/[^a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s]/g, '');
        if (maxLength && this.value.length > maxLength) {
            this.value = this.value.slice(0, maxLength);
        }
    });

    campo.addEventListener('keypress', function(e) {
        const char = e.key;
        if (!/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s]$/.test(char) && 
            !['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab', 'Enter', ' '].includes(e.key)) {
            e.preventDefault();
        }
    });

    campo.addEventListener('paste', function(e) {
        e.preventDefault();
        const text = (e.clipboardData || window.clipboardData).getData('text');
        const cleanedText = text.replace(/[^a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s]/g, '');
        if (maxLength) {
            document.execCommand('insertText', false, cleanedText.slice(0, maxLength));
        } else {
            document.execCommand('insertText', false, cleanedText);
        }
    });
}

// ========== FUNCIONALIDADES GLOBALES ==========
function configurarContadoresTextarea() {
    const textareas = document.querySelectorAll('textarea[maxlength]');
    
    textareas.forEach(textarea => {
        const maxLength = parseInt(textarea.getAttribute('maxlength'));
        const counter = document.createElement('div');
        counter.className = 'textarea-counter';
        counter.style.cssText = 'font-size: 12px; color: #666; text-align: right; margin-top: 5px;';
        counter.textContent = `0/${maxLength}`;
        
        textarea.parentNode.appendChild(counter);
        
        textarea.addEventListener('input', function() {
            const currentLength = this.value.length;
            counter.textContent = `${currentLength}/${maxLength}`;
            
            if (currentLength > maxLength) {
                this.value = this.value.substring(0, maxLength);
                counter.textContent = `${maxLength}/${maxLength}`;
                counter.style.color = '#dc3545';
            } else if (currentLength > maxLength * 0.9) {
                counter.style.color = '#ffc107';
            } else {
                counter.style.color = '#666';
            }
        });
        
        // Inicializar contador
        const initialLength = textarea.value.length;
        counter.textContent = `${initialLength}/${maxLength}`;
        if (initialLength > maxLength * 0.9) {
            counter.style.color = '#ffc107';
        }
    });
}

function configurarGeneradorCodigoBarras() {
    const generarCodigoBtn = document.getElementById('generarCodigo');
    if (generarCodigoBtn) {
        generarCodigoBtn.addEventListener('click', function() {
            // Generar código más robusto
            const timestamp = Date.now().toString();
            const randomNum = Math.floor(Math.random() * 10000).toString().padStart(4, '0');
            const codigo = (timestamp + randomNum).substring(0, 50);
            
            const codigoBarrasInput = document.getElementById('codigo_barras');
            if (codigoBarrasInput) {
                codigoBarrasInput.value = codigo;
                mostrarNotificacion('Código generado automáticamente', 'success');
            }
        });
    }
}

// ========== FUNCIONES UTILITARIAS ==========
function filtrarTabla(tablaId, termino) {
    const tabla = document.getElementById(tablaId);
    if (!tabla) return;
    
    const filas = tabla.querySelectorAll('tbody tr');
    const terminoLower = termino.toLowerCase();
    
    filas.forEach(fila => {
        const texto = fila.textContent.toLowerCase();
        if (texto.includes(terminoLower)) {
            fila.style.display = '';
        } else {
            fila.style.display = 'none';
        }
    });
}

function filtrarTablaPorEstatus(tablaId, estatus) {
    const tabla = document.getElementById(tablaId);
    if (!tabla) return;
    
    const filas = tabla.querySelectorAll('tbody tr');
    
    filas.forEach(fila => {
        const celdaEstatus = fila.cells[6]; // Columna de estatus (índice puede variar)
        if (!celdaEstatus) {
            fila.style.display = 'none';
            return;
        }
        
        const textoEstatus = celdaEstatus.textContent.trim();
        
        if (estatus === '' || textoEstatus === estatus) {
            fila.style.display = '';
        } else {
            fila.style.display = 'none';
        }
    });
}

// ========== FUNCIONES GLOBALES ==========
function copiarCodigo(codigo) {
    navigator.clipboard.writeText(codigo).then(function() {
        mostrarNotificacion('Código copiado: ' + codigo, 'success');
    }).catch(function(err) {
        console.error('Error al copiar: ', err);
        // Fallback para navegadores antiguos
        const tempInput = document.createElement('input');
        tempInput.value = codigo;
        document.body.appendChild(tempInput);
        tempInput.select();
        document.execCommand('copy');
        document.body.removeChild(tempInput);
        mostrarNotificacion('Código copiado: ' + codigo, 'success');
    });
}

function mostrarNotificacion(mensaje, tipo = 'info') {
    // Crear elemento de notificación
    const notificacion = document.createElement('div');
    notificacion.className = `notificacion ${tipo}`;
    notificacion.textContent = mensaje;
    
    // Estilos básicos para la notificación
    notificacion.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 12px 20px;
        border-radius: 4px;
        color: white;
        z-index: 10000;
        font-weight: bold;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        transition: all 0.3s ease;
    `;
    
    // Colores según el tipo
    if (tipo === 'success') {
        notificacion.style.backgroundColor = '#28a745';
    } else if (tipo === 'error') {
        notificacion.style.backgroundColor = '#dc3545';
    } else {
        notificacion.style.backgroundColor = '#007bff';
    }
    
    document.body.appendChild(notificacion);
    
    // Remover después de 3 segundos
    setTimeout(() => {
        notificacion.style.opacity = '0';
        notificacion.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (notificacion.parentNode) {
                notificacion.parentNode.removeChild(notificacion);
            }
        }, 300);
    }, 3000);
}