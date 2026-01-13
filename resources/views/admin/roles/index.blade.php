<x-app-layout>
    @section('title', 'Gestión de Roles')

    <header class="mb-8 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Gestión de Roles y Permisos</h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">Administra roles y sus permisos</p>
        </div>
        <button onclick="openCreateRoleModal()" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/80 transition">
            <i class="fa-solid fa-plus mr-2"></i>Crear Rol
        </button>
    </header>

    <!-- Grid de Roles -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($roles as $role)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $role->name }}</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        {{ $role->users->count() }} usuario(s)
                    </p>
                </div>
                @if(!in_array($role->name, ['super_admin', 'admin_rrhh', 'supervisor', 'viewer']))
                <button onclick="deleteRole({{ $role->id }}, '{{ $role->name }}')" class="text-red-600 hover:text-red-800 dark:text-red-400">
                    <i class="fa-solid fa-trash"></i>
                </button>
                @endif
            </div>

            <div class="mb-4">
                <p class="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase mb-2">
                    Permisos ({{ $role->permissions->count() }})
                </p>
                <div class="flex flex-wrap gap-1 max-h-32 overflow-y-auto">
                    @forelse($role->permissions->take(6) as $permission)
                    <span class="px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded text-xs">
                        {{ $permission->name }}
                    </span>
                    @empty
                    <span class="text-xs text-gray-400">Sin permisos</span>
                    @endforelse
                    @if($role->permissions->count() > 6)
                    <span class="text-xs text-gray-500">+{{ $role->permissions->count() - 6 }} más</span>
                    @endif
                </div>
            </div>

            <button onclick="openEditPermissionsModal({{ $role->id }}, '{{ $role->name }}', {{ $role->permissions->pluck('name') }})" class="w-full px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700 transition text-sm">
                <i class="fa-solid fa-edit mr-2"></i>Editar Permisos
            </button>
        </div>
        @endforeach
    </div>

    <!-- Modal: Crear Rol -->
    <div id="create-role-modal" class="fixed inset-0 z-50 hidden" role="dialog">
        <div class="fixed inset-0 bg-gray-900/60" onclick="closeCreateRoleModal()"></div>
        <div class="fixed inset-0 flex items-center justify-center p-4 overflow-y-auto">
            <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-3xl w-full p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Crear Nuevo Rol</h3>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nombre del Rol:</label>
                    <input type="text" id="new-role-name" placeholder="Ej: analista" class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 dark:text-white px-3 py-2">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Permisos:</label>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3 max-h-64 overflow-y-auto border border-gray-300 dark:border-gray-600 rounded p-3">
                        @foreach($permissions as $module => $perms)
                        <div class="col-span-full">
                            <h4 class="font-bold text-sm text-gray-700 dark:text-gray-300 mb-1 mt-2">{{ ucfirst($module) }}</h4>
                        </div>
                        @foreach($perms as $permission)
                        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 p-2 rounded">
                            <input type="checkbox" name="new_permissions[]" value="{{ $permission->name }}" class="rounded text-primary">
                            <span>{{ $permission->name }}</span>
                        </label>
                        @endforeach
                        @endforeach
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <button onclick="closeCreateRoleModal()" class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-white rounded hover:bg-gray-400 transition">
                        Cancelar
                    </button>
                    <button onclick="createRole()" class="px-4 py-2 bg-primary text-white rounded hover:bg-primary/80 transition">
                        Crear Rol
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Editar Permisos -->
    <div id="edit-permissions-modal" class="fixed inset-0 z-50 hidden" role="dialog">
        <div class="fixed inset-0 bg-gray-900/60" onclick="closeEditPermissionsModal()"></div>
        <div class="fixed inset-0 flex items-center justify-center p-4 overflow-y-auto">
            <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-3xl w-full p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Editar Permisos</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Rol: <strong id="edit-role-name"></strong></p>

                <div class="mb-4">
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3 max-h-96 overflow-y-auto border border-gray-300 dark:border-gray-600 rounded p-3">
                        @foreach($permissions as $module => $perms)
                        <div class="col-span-full">
                            <h4 class="font-bold text-sm text-gray-700 dark:text-gray-300 mb-1 mt-2">{{ ucfirst($module) }}</h4>
                        </div>
                        @foreach($perms as $permission)
                        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 p-2 rounded">
                            <input type="checkbox" name="edit_permissions[]" value="{{ $permission->name }}" class="rounded text-primary edit-permission-checkbox">
                            <span>{{ $permission->name }}</span>
                        </label>
                        @endforeach
                        @endforeach
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <button onclick="closeEditPermissionsModal()" class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-white rounded hover:bg-gray-400 transition">
                        Cancelar
                    </button>
                    <button onclick="updatePermissions()" class="px-4 py-2 bg-primary text-white rounded hover:bg-primary/80 transition">
                        Guardar Cambios
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        let currentRoleId = null;

        function openCreateRoleModal() {
            document.getElementById('create-role-modal').classList.remove('hidden');
        }

        function closeCreateRoleModal() {
            document.getElementById('create-role-modal').classList.add('hidden');
            document.getElementById('new-role-name').value = '';
            document.querySelectorAll('input[name="new_permissions[]"]').forEach(cb => cb.checked = false);
        }

        async function createRole() {
            const name = document.getElementById('new-role-name').value.trim();
            if (!name) {
                alert('Ingresa un nombre para el rol');
                return;
            }

            const permissions = Array.from(document.querySelectorAll('input[name="new_permissions[]"]:checked')).map(cb => cb.value);

            try {
                const response = await fetch('/admin/roles', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ name, permissions })
                });

                const result = await response.json();

                if (response.ok) {
                    alert(result.message);
                    location.reload();
                } else {
                    alert(result.error || 'Error al crear rol');
                }
            } catch (error) {
                console.error(error);
                alert('Error de conexión');
            }
        }

        function openEditPermissionsModal(roleId, roleName, currentPermissions) {
            currentRoleId = roleId;
            document.getElementById('edit-role-name').textContent = roleName;

            // Desmarcar todos
            document.querySelectorAll('.edit-permission-checkbox').forEach(cb => cb.checked = false);

            // Marcar los permisos actuales
            currentPermissions.forEach(permission => {
                const checkbox = document.querySelector(`.edit-permission-checkbox[value="${permission}"]`);
                if (checkbox) checkbox.checked = true;
            });

            document.getElementById('edit-permissions-modal').classList.remove('hidden');
        }

        function closeEditPermissionsModal() {
            document.getElementById('edit-permissions-modal').classList.add('hidden');
        }

        async function updatePermissions() {
            const permissions = Array.from(document.querySelectorAll('.edit-permission-checkbox:checked')).map(cb => cb.value);

            try {
                const response = await fetch(`/admin/roles/${currentRoleId}/permissions`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ permissions })
                });

                const result = await response.json();

                if (response.ok) {
                    alert(result.message);
                    location.reload();
                } else {
                    alert(result.error || 'Error al actualizar permisos');
                }
            } catch (error) {
                console.error(error);
                alert('Error de conexión');
            }
        }

        async function deleteRole(roleId, roleName) {
            if (!confirm(`¿Eliminar el rol "${roleName}"?`)) return;

            try {
                const response = await fetch(`/admin/roles/${roleId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                });

                const result = await response.json();

                if (response.ok) {
                    alert(result.message);
                    location.reload();
                } else {
                    alert(result.error || 'Error al eliminar rol');
                }
            } catch (error) {
                console.error(error);
                alert('Error de conexión');
            }
        }
    </script>
</x-app-layout>
