// Funcionalidad de búsqueda y filtrado
document.addEventListener('DOMContentLoaded', function() {
    console.log('JavaScript cargado correctamente');
    
    // Obtener la página actual
    const currentPage = window.location.href;
    console.log('Página actual:', currentPage);
    
    // ========== BÚSQUEDA Y FILTRADO ==========
    
    // Búsqueda para trabajadores (solo en página de gestión)
    if (currentPage.includes('pagina=gestion') || currentPage.includes('GestionTrabajador.php')) {
        const buscarTrabajadores = document.getElementById('buscarTrabajadores');
        if (buscarTrabajadores) {
            console.log('Configurando búsqueda de trabajadores');
            buscarTrabajadores.addEventListener('input', function() {
                const termino = this.value.toLowerCase();
                const tabla = document.getElementById('tablaTrabajadores');
                if (!tabla) return;
                
                const filas = tabla.querySelectorAll('tbody tr');
                
                filas.forEach(fila => {
                    const texto = fila.textContent.toLowerCase();
                    if (texto.includes(termino)) {
                        fila.style.display = '';
                    } else {
                        fila.style.display = 'none';
                    }
                });
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

        // ========== VALIDACIONES PARA TRABAJADORES ==========
        console.log('Configurando validaciones para trabajadores');
        
        const nombreTrabajador = document.getElementById('nombre');
        if (nombreTrabajador) {
            nombreTrabajador.addEventListener('input', function() {
                this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '');
            });

            nombreTrabajador.addEventListener('keypress', function(e) {
                const char = e.key;
                if (!/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]$/.test(char) && 
                    !['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab', 'Enter', ' '].includes(e.key)) {
                    e.preventDefault();
                }
            });
        }

        const paternoTrabajador = document.getElementById('paterno');
        if (paternoTrabajador) {
            paternoTrabajador.addEventListener('input', function() {
                this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '');
            });

            paternoTrabajador.addEventListener('keypress', function(e) {
                const char = e.key;
                if (!/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]$/.test(char) && 
                    !['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab', 'Enter', ' '].includes(e.key)) {
                    e.preventDefault();
                }
            });
        }

        const maternoTrabajador = document.getElementById('materno');
        if (maternoTrabajador) {
            maternoTrabajador.addEventListener('input', function() {
                this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '');
            });

            maternoTrabajador.addEventListener('keypress', function(e) {
                const char = e.key;
                if (!/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]$/.test(char) && 
                    !['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab', 'Enter', ' '].includes(e.key)) {
                    e.preventDefault();
                }
            });
        }

        const numeroControl = document.getElementById('numero_control');
        if (numeroControl) {
            numeroControl.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
                if (this.value.length > 8) {
                    this.value = this.value.slice(0, 8);
                }
            });

            numeroControl.addEventListener('keypress', function(e) {
                const char = e.key;
                if (!/^[0-9]$/.test(char) && 
                    !['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab', 'Enter'].includes(e.key)) {
                    e.preventDefault();
                }
            });
        }

        const pinTrabajador = document.getElementById('pin');
        if (pinTrabajador) {
            pinTrabajador.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
                if (this.value.length > 4) {
                    this.value = this.value.slice(0, 4);
                }
            });

            pinTrabajador.addEventListener('keypress', function(e) {
                const char = e.key;
                if (!/^[0-9]$/.test(char) && 
                    !['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab', 'Enter'].includes(e.key)) {
                    e.preventDefault();
                }
            });
        }

        const telefonoTrabajador = document.getElementById('telefono');
        if (telefonoTrabajador) {
            telefonoTrabajador.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
                if (this.value.length > 10) {
                    this.value = this.value.slice(0, 10);
                }
            });

            telefonoTrabajador.addEventListener('keypress', function(e) {
                const char = e.key;
                if (!/^[0-9]$/.test(char) && 
                    !['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab', 'Enter'].includes(e.key)) {
                    e.preventDefault();
                }
            });
        }
    }

    // ========== LABORATORIOS ==========
    if (currentPage.includes('pagina=laboratorios')) {
        console.log('Configurando funcionalidades para laboratorios');
        
        // Búsqueda para laboratorios
        const buscarLaboratorios = document.getElementById('buscarLaboratorios');
        if (buscarLaboratorios) {
            console.log('Configurando búsqueda de laboratorios');
            buscarLaboratorios.addEventListener('input', function() {
                const termino = this.value.toLowerCase();
                const tabla = document.getElementById('tablaLaboratorios');
                if (!tabla) return;
                
                const filas = tabla.querySelectorAll('tbody tr');
                
                filas.forEach(fila => {
                    const texto = fila.textContent.toLowerCase();
                    if (texto.includes(termino)) {
                        fila.style.display = '';
                    } else {
                        fila.style.display = 'none';
                    }
                });
            });
        }

        // Validación para nombre del laboratorio (solo letras y espacios)
        const nombreLab = document.getElementById('nombre_lab');
        if (nombreLab) {
            console.log('Configurando validación para nombre_lab');
            
            nombreLab.addEventListener('input', function() {
                console.log('Input event en nombre_lab:', this.value);
                // Limpiar cualquier carácter no permitido
                this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '');
                if (this.value.length > 50) {
                    this.value = this.value.slice(0, 50);
                }
            });

            nombreLab.addEventListener('keypress', function(e) {
                console.log('Keypress en nombre_lab:', e.key);
                // Permitir solo letras, espacios y teclas de control
                const char = e.key;
                if (!/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]$/.test(char) && 
                    !['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab', 'Enter', ' '].includes(e.key)) {
                    e.preventDefault();
                    console.log('Carácter bloqueado:', char);
                }
            });

            // Prevenir pegado de contenido no válido
            nombreLab.addEventListener('paste', function(e) {
                e.preventDefault();
                const text = (e.clipboardData || window.clipboardData).getData('text');
                const cleanedText = text.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '');
                document.execCommand('insertText', false, cleanedText);
            });
        }

        // Validación para ubicación del laboratorio (letras y números)
        const ubicacionLab = document.getElementById('ubicacion');
        if (ubicacionLab) {
            console.log('Configurando validación para ubicacion');
            
            ubicacionLab.addEventListener('input', function() {
                // Limpiar cualquier carácter no permitido
                this.value = this.value.replace(/[^a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s]/g, '');
                if (this.value.length > 30) {
                    this.value = this.value.slice(0, 30);
                }
            });

            ubicacionLab.addEventListener('keypress', function(e) {
                // Permitir solo letras, números, espacios y teclas de control
                const char = e.key;
                if (!/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s]$/.test(char) && 
                    !['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab', 'Enter', ' '].includes(e.key)) {
                    e.preventDefault();
                }
            });

            ubicacionLab.addEventListener('paste', function(e) {
                e.preventDefault();
                const text = (e.clipboardData || window.clipboardData).getData('text');
                const cleanedText = text.replace(/[^a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s]/g, '');
                document.execCommand('insertText', false, cleanedText);
            });
        }
    }

    // ========== INSUMOS ==========
    if (currentPage.includes('pagina=insumos')) {
        console.log('Configurando funcionalidades para insumos');
        
        // Búsqueda para insumos
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
            
            nombreInsumo.addEventListener('input', function() {
                // Limpiar cualquier carácter no permitido
                this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '');
                if (this.value.length > 30) {
                    this.value = this.value.slice(0, 30);
                }
            });

            nombreInsumo.addEventListener('keypress', function(e) {
                // Permitir solo letras, espacios y teclas de control
                const char = e.key;
                if (!/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]$/.test(char) && 
                    !['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab', 'Enter', ' '].includes(e.key)) {
                    e.preventDefault();
                }
            });

            nombreInsumo.addEventListener('paste', function(e) {
                e.preventDefault();
                const text = (e.clipboardData || window.clipboardData).getData('text');
                const cleanedText = text.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '');
                document.execCommand('insertText', false, cleanedText);
            });
        }

        // Validación para código de barras (solo números)
        const codigoBarras = document.getElementById('codigo_barras');
        if (codigoBarras) {
            console.log('Configurando validación para codigo_barras');
            
            codigoBarras.addEventListener('input', function() {
                // Limpiar cualquier carácter no permitido
                this.value = this.value.replace(/[^0-9]/g, '');
                if (this.value.length > 50) {
                    this.value = this.value.slice(0, 50);
                }
            });

            codigoBarras.addEventListener('keypress', function(e) {
                // Permitir solo números y teclas de control
                const char = e.key;
                if (!/^[0-9]$/.test(char) && 
                    !['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab', 'Enter'].includes(e.key)) {
                    e.preventDefault();
                }
            });

            codigoBarras.addEventListener('paste', function(e) {
                e.preventDefault();
                const text = (e.clipboardData || window.clipboardData).getData('text');
                const cleanedText = text.replace(/[^0-9]/g, '');
                document.execCommand('insertText', false, cleanedText);
            });
        }

        // Validación para cantidad (solo números)
        const cantidadInsumo = document.getElementById('cantidad');
        if (cantidadInsumo) {
            console.log('Configurando validación para cantidad');
            
            cantidadInsumo.addEventListener('input', function() {
                // Limpiar cualquier carácter no permitido
                this.value = this.value.replace(/[^0-9]/g, '');
                if (this.value.length > 3) {
                    this.value = this.value.slice(0, 3);
                }
                if (parseInt(this.value) > 999) {
                    this.value = '999';
                }
            });

            cantidadInsumo.addEventListener('keypress', function(e) {
                // Permitir solo números y teclas de control
                const char = e.key;
                if (!/^[0-9]$/.test(char) && 
                    !['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab', 'Enter'].includes(e.key)) {
                    e.preventDefault();
                }
            });

            cantidadInsumo.addEventListener('paste', function(e) {
                e.preventDefault();
                const text = (e.clipboardData || window.clipboardData).getData('text');
                const cleanedText = text.replace(/[^0-9]/g, '');
                document.execCommand('insertText', false, cleanedText);
            });
        }

        // Generar código de barras automático
        const generarCodigoBtn = document.getElementById('generarCodigo');
        if (generarCodigoBtn) {
            console.log('Configurando botón generar código');
            generarCodigoBtn.addEventListener('click', function() {
                const timestamp = Date.now().toString();
                const randomNum = Math.floor(Math.random() * 1000).toString().padStart(3, '0');
                const codigo = timestamp + randomNum;
                // Limitar a 50 caracteres
                document.getElementById('codigo_barras').value = codigo.substring(0, 50);
            });
        }
    }

    console.log('Configuración completada para página:', currentPage);
});

// ========== FUNCIONES GLOBALES ==========

// Función para copiar código de barras
function copiarCodigo(codigo) {
    navigator.clipboard.writeText(codigo).then(function() {
        alert('Código copiado: ' + codigo);
    }).catch(function(err) {
        console.error('Error al copiar: ', err);
        // Fallback para navegadores antiguos
        const tempInput = document.createElement('input');
        tempInput.value = codigo;
        document.body.appendChild(tempInput);
        tempInput.select();
        document.execCommand('copy');
        document.body.removeChild(tempInput);
        alert('Código copiado: ' + codigo);
    });
}