<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2016 Joe Nilson                joenilson@gmail.com
 * Copyright (C) 2017  Francesc Pineda Segarra  francesc.pineda@x-netdigital.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Description of admin_canarias
 *
 * @author Francesc Pineda Segarra
 */
class admin_login extends fs_controller {

    public function __construct() {
        parent::__construct(__CLASS__, 'Login anónimo', 'admin');
    }

    protected function private_core() {
        $this->checkNewInstallation();
        
        if (isset($_GET['opcion'])) {
            if ($_GET['opcion'] == 'chat_soporte') {
                if ($_GET['status'] == 'disable') {
                    $this->desactivarJsChat();
                } else {
                    $this->activarJsChat();
                }
            }
        } else {
            $this->check_menu();
        }
    }

    private function check_menu() {

        // Limpiamos la cache por si ha habido cambio en la estructura de las tablas
        $this->cache->clean();

        if (file_exists(__DIR__)) {
            /// activamos las páginas del plugin
            foreach (scandir(__DIR__) as $f) {
                if ($f != '.' AND $f != '..' AND is_string($f) AND strlen($f) > 4 AND ! is_dir($f) AND $f != __CLASS__ . '.php') {
                    $page_name = substr($f, 0, -4);

                    require_once __DIR__ . '/' . $f;
                    $new_fsc = new $page_name();

                    if (!$new_fsc->page->save()) {
                        $this->new_error_msg("Imposible guardar la página " . $page_name);
                    }

                    unset($new_fsc);
                }
            }
        } else {
            $this->new_error_msg('No se encuentra el directorio ' . __DIR__);
        }

        $this->load_menu(TRUE);
    }

    /**
     * Devuelve si el chat de soporte está o no activado
     * 
     * @return boolean
     */
    public function chat_soporte_ok() {
        $fsvar = new fs_var();
        $chat_soporte_xnet = (bool) $fsvar->simple_get('chat_soporte_xnet');

        if ($chat_soporte_xnet) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * Activa el chat de soporte en todas las páginas como extensión
     */
    private function activarJsChat() {
        $items = '<script type="text/javascript" src="' . FS_PATH . 'plugins/login_anonimo/view/js/chat_soporte.js"></script>';

        $extensions = array(
            array(
                'name' => 'chat_soporte_xnet',
                'page_from' => __CLASS__,
                'page_to' => NULL,
                'type' => 'head',
                'text' => $items,
                'params' => ''
            ),
        );
        foreach ($extensions as $ext) {
            $fsext = new fs_extension($ext);
            $fsext->save();
        }
        
        $fsvar = new fs_var();
        $fsvar->simple_save('chat_soporte_xnet', TRUE);
        $this->new_message('Chat de soporte activado.');
    }
    
    /**
     * Desactiva el chat de soporte de todas las páginas (requiere cambiar de página para que desaparezca)
     */
    private function desactivarJsChat() {
        $pluginRequireChat = 'plugins/ayuda_soporte_mifactura';
        
        if (file_exists($pluginRequireChat) && is_dir($pluginRequireChat)) {
            $this->new_error_msg('El chat de soporte no se puede desactivar porque estás hospedado en <a target="_blank" href="https://mifactura.eu">https://mifactura.eu</a> o has contrato nuestro soporte.');
        } else {
            $fsext = new fs_extension();
            $fsext = $fsext->get('chat_soporte_xnet', __CLASS__);
            $fsext->delete();
        
            $fsvar = new fs_var();
            $fsvar->simple_delete('chat_soporte_xnet');
            $this->new_message('Chat de soporte desactivado. <a href="'.$this->url().'">Recargar para comprobar que ya no está el chat</a>.');
        }
    }
    
    /**
     * Comprueba si es una instalación nueva, y si lo es, se pre-activa el chat de soporte
     */
    private function checkNewInstallation()
    {
        $fsvar = new fs_var();
        $fsvar = $fsvar->simple_get('login_anonimo_instalado');
        if(!$fsvar) {
            $fsvar = new fs_var();
            $fsvar->simple_save('login_anonimo_instalado', TRUE);
            $this->activarJsChat();
        }
    }
}
