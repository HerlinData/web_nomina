<?php

namespace App\View\Components\Ui;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ActionButton extends Component
{
    // Propiedades públicas para el componente
    public string $icon;
    public string $title;
    public string $color;

    /**
     * Create a new component instance.
     */
    public function __construct(
        public string $type = '',
        ?string $icon = null,
        ?string $title = null,
        ?string $color = null
    ) {
        $defaultProps = [
            'view' => ['icon' => 'fa-eye', 'title' => 'Ver', 'color' => 'blue'],
            'edit' => ['icon' => 'fa-pen', 'title' => 'Editar', 'color' => 'primary'],
            'delete' => ['icon' => 'fa-trash', 'title' => 'Eliminar', 'color' => 'red'],
            'add' => ['icon' => 'fa-plus', 'title' => 'Añadir', 'color' => 'primary'],
            'baja' => ['icon' => 'fa-person-walking-arrow-right', 'title' => 'Dar de Baja', 'color' => 'red'],
        ];

        // Establecer valores por defecto basados en el tipo
        $defaults = $defaultProps[$type] ?? [];

        // Prioridad:
        // 1. Valor explícito pasado al componente.
        // 2. Valor por defecto del tipo.
        // 3. Valor por defecto del constructor de la propiedad.
        $this->icon = $icon ?? ($defaults['icon'] ?? '');
        $this->title = $title ?? ($defaults['title'] ?? '');
        $this->color = $color ?? ($defaults['color'] ?? 'gray');
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.ui.action-button');
    }

    /**
     * Get the hover color classes for the button.
     */
    public function hoverClass(): string
    {
        return [
            'blue' => 'hover:bg-blue-500',
            'yellow' => 'hover:bg-yellow-500',
            'red' => 'hover:bg-red-700',
            'primary' => 'hover:bg-primary',
            'gray' => 'hover:bg-gray-500',
            'gray' => 'hover:bg-gray-500',
        ][$this->color] ?? 'hover:bg-gray-500';
    }
}
