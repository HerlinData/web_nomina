<!-- MODAL HISTORIAL PREVIA (muestra contratos antes de crear) -->
<div id="historial-previa-modal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto">
        <div class="fixed inset-0 bg-gray-900/60 transition-opacity" onclick="closeModal('historial-previa-modal')" style="backdrop-filter: blur(5px);"></div>

        <div class="relative z-10 w-full transform overflow-hidden rounded-xl bg-white dark:bg-dark-card text-left shadow-2xl border border-light-border dark:border-dark-border" style="max-width: 1200px;">
            <div class="bg-white dark:bg-dark-card px-8 py-6 border-b border-light-border dark:border-dark-border flex justify-between items-center">
                <div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">Historial de Contratos</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1" id="historial-persona-info"></p>
                </div>
                <button onclick="closeModal('historial-previa-modal')" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fa-solid fa-times text-xl"></i>
                </button>
            </div>

            <div class="p-8">
                <!-- Mensaje informativo -->
                <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                    <div class="flex items-start gap-3">
                        <i class="fa-solid fa-circle-info text-blue-500 mt-0.5"></i>
                        <div>
                            <p class="text-sm text-blue-800 dark:text-blue-300">
                                Esta persona ya tiene contratos registrados. Revise el historial antes de continuar.
                            </p>
                            <p class="text-xs text-blue-600 dark:text-blue-400 mt-1">
                                Haga clic en una fila para ver los movimientos del contrato.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Tabla de Historial -->
                <div class="overflow-x-auto px-2">
                    <table class="w-full text-center" style="border-collapse: separate; border-spacing: 0 4px;" id="tabla-historial-previa">
                        <thead>
                            <tr>
                                <th class="px-6 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-left">Colaborador</th>
                                <th class="px-6 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Cargo</th>
                                <th class="px-6 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Salario</th>
                                <th class="px-6 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Inicio</th>
                                <th class="px-6 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Fin</th>
                                <th class="px-6 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Estado</th>
                            </tr>
                        </thead>
                        <tbody id="tabla-historial-body">
                            <!-- Se llenara dinamicamente con JavaScript -->
                        </tbody>
                    </table>
                </div>

                <!-- Boton Continuar -->
                <div class="mt-8 flex justify-end gap-3 pt-4 border-t border-light-border dark:border-dark-border">
                    <x-forms.secondary-button type="button" onclick="closeModal('historial-previa-modal')">
                        Cancelar
                    </x-forms.secondary-button>
                    <x-forms.primary-button type="button" id="btn-continuar-crear">
                        <i class="fa-solid fa-arrow-right mr-2"></i>
                        Continuar con Nuevo Contrato
                    </x-forms.primary-button>
                </div>
            </div>
        </div>
    </div>
</div>
