<!-- MODAL EVALUAR CONTRATO (Popup inicial) -->
<div id="evaluar-contrato-modal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-gray-900/60 transition-opacity" onclick="closeModal('evaluar-contrato-modal')" style="backdrop-filter: blur(5px);"></div>

        <div class="relative z-10 w-full transform overflow-hidden rounded-xl bg-white dark:bg-dark-card text-left shadow-2xl border border-light-border dark:border-dark-border" style="max-width: 500px;">
            <div class="bg-white dark:bg-dark-card px-8 py-6 border-b border-light-border dark:border-dark-border flex justify-between items-center">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">Nuevo Contrato</h3>
                <button onclick="closeModal('evaluar-contrato-modal')" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fa-solid fa-times text-xl"></i>
                </button>
            </div>

            <div class="p-8">
                <form id="form-evaluar-contrato">
                    <div class="space-y-6">
                        <!-- Numero de Documento -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                Numero de Documento
                            </label>
                            <input
                                type="text"
                                id="evaluar-numero-documento"
                                name="numero_documento"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 py-2.5 px-4"
                                placeholder="Ej: 12345678"
                                required
                            >
                            <div id="evaluar-persona-nombre" class="text-sm text-gray-500 dark:text-gray-400 mt-2 h-4 transition-all duration-300"></div>
                        </div>

                        <!-- Fecha de Inicio -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                Inicio del Contrato
                            </label>
                            <input
                                type="date"
                                id="evaluar-fecha-inicio"
                                name="fecha_inicio"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 py-2.5 px-4 disabled:bg-gray-100 disabled:dark:bg-gray-700"
                                required
                                disabled
                            >
                        </div>
                    </div>

                    <div class="mt-8 flex justify-end gap-3 pt-4 border-t border-light-border dark:border-dark-border">
                        <x-forms.secondary-button type="button" onclick="closeModal('evaluar-contrato-modal')">
                            Cancelar
                        </x-forms.secondary-button>
                        <x-forms.primary-button type="submit" id="btn-evaluar-contrato">
                            Evaluar
                        </x-forms.primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
