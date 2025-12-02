<?php
// ticket_template.php - Template del ticket
function generarHTMLTicket($ticket_data) {
    ob_start();
?>
<div class="ticket-section" id="ticketSection">
    <div class="section-header">
        <h2><i class="fas fa-receipt"></i> TICKET GENERADO</h2>
        <button onclick="cerrarTicket()" class="btn-cerrar-ticket">
            <i class="fas fa-times"></i> CERRAR
        </button>
    </div>
    
    <div class="ticket-container">
        <div class="ticket-header">
            <h1>SOLICITUD DE INSUMOS</h1>
            <p class="subtitle">Sistema de Laboratorios</p>
        </div>
        
        <div class="ticket-content">
            <div class="info-section">
                <h3>INFORMACIÓN</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">FECHA:</span>
                        <span class="info-value"><?php echo $ticket_data['fecha']; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">TICKET:</span>
                        <span class="info-value">#<?php echo $ticket_data['numero_ticket']; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">SOLICITANTE:</span>
                        <span class="info-value"><?php echo htmlspecialchars($ticket_data['maestro']['NOMBRE'] . ' ' . $ticket_data['maestro']['PATERNO']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">LABORATORIO:</span>
                        <span class="info-value"><?php echo htmlspecialchars($ticket_data['laboratorio']['NOM_LAB']); ?></span>
                    </div>
                </div>
            </div>

            <div class="info-section">
                <h3>INSUMOS SOLICITADOS</h3>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>INSUMO</th>
                                <th style="text-align: center;">CANT</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($ticket_data['solicitudes']->num_rows > 0): ?>
                                <?php 
                                $ticket_data['solicitudes']->data_seek(0);
                                $total_insumos = 0;
                                while ($solicitud = $ticket_data['solicitudes']->fetch_assoc()): 
                                    if ($solicitud['CANTIDAD'] > 0): 
                                        $total_insumos++;
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($solicitud['NOMBRE']); ?></td>
                                        <td style="text-align: center; font-weight: bold;">
                                            <?php echo $solicitud['CANTIDAD']; ?>
                                        </td>
                                    </tr>
                                <?php 
                                    endif;
                                endwhile; 
                                ?>
                                <?php if ($total_insumos > 0): ?>
                                    <tr>
                                        <td style="text-align: right; font-weight: bold; border-top: 2px solid #000;">TOTAL:</td>
                                        <td style="text-align: center; font-weight: bold; border-top: 2px solid #000;">
                                            <?php 
                                            $ticket_data['solicitudes']->data_seek(0);
                                            $total_cantidad = 0;
                                            while ($solicitud = $ticket_data['solicitudes']->fetch_assoc()) {
                                                $total_cantidad += $solicitud['CANTIDAD'];
                                            }
                                            echo $total_cantidad;
                                            ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="2" style="text-align: center; padding: 15px; color: #666;">
                                        <i>No hay insumos solicitados</i>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="ticket-footer">
            <div class="status-badge">PENDIENTE DE ENTREGA</div>
            <p style="margin: 10px 0; color: #666; line-height: 1.3; font-size: 10px;">
                <strong>NOTA:</strong> Presente este ticket al encargado del laboratorio. 
                Válido por 24 horas.
            </p>
            <div class="action-buttons">
                <button class="btn btn-print" onclick="imprimirTicket()">
                    <i class="fas fa-print"></i> IMPRIMIR
                </button>
                <button class="btn btn-close" onclick="cerrarTicket()">
                    <i class="fas fa-times"></i> CERRAR
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    .ticket-section {
        margin-top: 30px;
        border: 1px solid #000;
        border-radius: 0;
        overflow: hidden;
        animation: slideIn 0.5s ease-in-out;
        background: white;
        max-width: 400px;
        margin-left: auto;
        margin-right: auto;
    }
    
    .section-header {
        background: #000;
        padding: 10px 15px;
        border-bottom: 1px solid #000;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .section-header h2 {
        margin: 0;
        color: white;
        flex-grow: 1;
        font-size: 16px;
        font-weight: bold;
    }
    
    .btn-cerrar-ticket {
        background: #666;
        color: white;
        border: none;
        padding: 5px 10px;
        border-radius: 3px;
        cursor: pointer;
        font-size: 12px;
    }
    
    .btn-cerrar-ticket:hover {
        background: #444;
    }
    
    .ticket-container {
        background: white;
        border: 2px solid #000;
    }
    
    .ticket-header {
        background: white;
        color: black;
        padding: 15px;
        text-align: center;
        border-bottom: 2px dashed #000;
    }
    
    .ticket-header h1 {
        font-size: 18px;
        margin-bottom: 5px;
        font-weight: bold;
        text-transform: uppercase;
    }
    
    .ticket-header .subtitle {
        font-size: 12px;
        color: #666;
    }
    
    .ticket-content {
        padding: 15px;
    }
    
    .info-section {
        margin-bottom: 15px;
        padding: 0;
        background: white;
        border-radius: 0;
        border-left: none;
    }
    
    .info-section h3 {
        color: black;
        margin-bottom: 10px;
        font-size: 14px;
        font-weight: bold;
        text-align: center;
        border-bottom: 1px solid #000;
        padding-bottom: 5px;
    }
    
    .info-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 8px;
    }
    
    .info-item {
        margin-bottom: 5px;
        display: flex;
        justify-content: space-between;
        border-bottom: 1px dotted #ccc;
        padding-bottom: 3px;
    }
    
    .info-label {
        font-weight: bold;
        color: black;
        font-size: 12px;
    }
    
    .info-value {
        color: black;
        font-size: 12px;
        text-align: right;
    }
    
    .table-container {
        overflow-x: auto;
        margin: 10px 0;
    }
    
    table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        border-radius: 0;
        overflow: hidden;
    }
    
    th {
        background: #f0f0f0;
        color: black;
        padding: 8px 5px;
        text-align: left;
        font-weight: bold;
        font-size: 11px;
        border: 1px solid #000;
    }
    
    td {
        padding: 6px 5px;
        border: 1px solid #000;
        font-size: 11px;
    }
    
    tr:nth-child(even) {
        background: #f8f8f8;
    }
    
    .ticket-footer {
        background: white;
        padding: 15px;
        border-top: 2px dashed #000;
        text-align: center;
    }
    
    .status-badge {
        display: inline-block;
        padding: 4px 8px;
        background: #000;
        color: white;
        border-radius: 3px;
        font-weight: bold;
        font-size: 10px;
        margin-bottom: 10px;
    }
    
    .action-buttons {
        margin-top: 10px;
        display: flex;
        gap: 8px;
        justify-content: center;
    }
    
    .btn {
        padding: 8px 15px;
        border: 1px solid #000;
        border-radius: 3px;
        font-size: 12px;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.3s ease;
        background: white;
        color: black;
    }
    
    .btn-print {
        background: black;
        color: white;
    }
    
    .btn-print:hover {
        background: #333;
    }
    
    .btn-close:hover {
        background: #f0f0f0;
    }
    
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @media print {
        body * {
            visibility: hidden;
        }
        .ticket-section, .ticket-section * {
            visibility: visible;
        }
        .ticket-section {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            max-width: 100%;
            border: none;
            margin: 0;
            box-shadow: none;
        }
        .section-header, .btn-cerrar-ticket, .action-buttons {
            display: none !important;
        }
        .ticket-container {
            border: 2px solid #000;
            margin: 0;
            padding: 0;
        }
    }
</style>

<script>
    function imprimirTicket() {
        window.print();
    }
    
    function cerrarTicket() {
        document.getElementById('ticketSection').style.display = 'none';
        // Limpiar el formulario después de cerrar el ticket
        document.getElementById('formSolicitudInsumos').reset();
        document.getElementById('insumos-container').style.display = 'none';
    }
    
    // Auto-scroll al ticket cuando se genera
    document.addEventListener('DOMContentLoaded', function() {
        const ticketSection = document.getElementById('ticketSection');
        if (ticketSection) {
            ticketSection.scrollIntoView({
                behavior: 'smooth'
            });
        }
    });
</script>
<?php
    return ob_get_clean();
}
?>