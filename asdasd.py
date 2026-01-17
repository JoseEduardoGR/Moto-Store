from flask import request, jsonify
import pyodbc  # Changed from pyodbc as requested
import datetime
import csv
import os
from curp import *



def verify_api_key(request):
    """Verifica que la solicitud tenga una API Key válida"""
    api_key = request.headers.get('API-Key')  # Asumiendo que la API Key está en el header
    if api_key != "TuClaveApiSecreta":  # Cambia esto a tu clave secreta
        return jsonify({'error': 'API Key inválida'}), 401
    return None  # Si la clave es válida, no se devuelve nada

def register_post_routes(app, db_config, verify_api_key):
    """Register all POST routes with the Flask app"""
    @app.route('/api/medidores/registro_previo', methods=['POST'])
    def insertar_registro_previo():
        # Verifica API Key
        api_check = check_api_key()
        if api_check:
            return api_check

        data = request.get_json()

        # Lista de campos requeridos en el orden exacto que espera la tabla
        requeridos = [
	        "pamClaveUsuario", "pamNumeroContrato", "pamNombreUsuario",
            "pamDireccion", "pamNumeroMedidor", "pamMarcaMedidor", "pamLecturista",
            "pamLecturaActual", "pamObservaciones", "pamCoordenadas", "pamIdColonia",
            "pamEstatus", "pamAtendidoPor", "pamActivo"
        ]

        valores = ["" if data.get(campo) is None else data.get(campo) for campo in requeridos]

        # Prepara los valores a insertar tal cual vengan (excepto pamActivo que siempre será 0)
        valores = [
            ("" if data.get(campo) is None else data.get(campo)) if campo != "pamActivo" else 0
            for campo in requeridos
        ]

        # Cadena de conexión
        driver = '{SQL Server}'
        conn_str = f'DRIVER={driver};SERVER={db_config["server"]};DATABASE={db_config["database"]};UID={db_config["username"]};PWD={db_config["password"]}'

        try:
            conn = pyodbc.connect(conn_str)
            cursor = conn.cursor()

            cursor.execute("""
                INSERT INTO [DBAGUA].[dbo].[Padron_Medidores_RegistroPrevio] (
                    pamFechaRegistro, pamClaveUsuario, pamNumeroContrato, pamNombreUsuario,
                    pamDireccion, pamNumeroMedidor, pamMarcaMedidor, pamLecturista,
                    pamLecturaActual, pamObservaciones, pamCoordenadas, pamIdColonia,
                    pamEstatus, pamAtendidoPor, pamActivo, pamFechaAtencion
                ) VALUES (
                    GETDATE(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL
                )
            """, tuple(valores))

            conn.commit()
            return jsonify({"message": "Registro previo insertado correctamente"}), 201

        except Exception as e:
            print(f"Error de base de datos: {str(e)}"*30)
            return jsonify({"error": f"Error de base de datos: {str(e)}"}), 500

    # Función para verificar la API Key
    def check_api_key():
        api_check = verify_api_key(request)
        if api_check:
            return api_check

    @app.route('/solicitar_acceso', methods=['POST'])
    def solicitar_acceso():
        data = request.get_json()
        curp = data.get('curp')
        uuid = data.get('uuid')

        # Validamos que se haya enviado la CURP
        if not curp:
            return jsonify({"error": "Falta el campo 'curp'"}), 400
        if not validar_curp(curp):
            return jsonify({"error": "El campo 'curp' es invalido"}), 422
        else:
            obtener_datos_curp(curp)
            juntar_tablas_datos_curp(uuid=uuid)

        return jsonify({"mensaje": "CURP registrada correctamente."}), 200
    @app.route('/api/tiempo_uso/<lecturista_id>', methods=['GET'])
    def obtener_tiempo_total(lecturista_id):
        
        # Cargar datos desde el archivo JSON
        def cargar_datos():
            if os.path.exists(ARCHIVO_JSON):
                with open(ARCHIVO_JSON, "r") as f:
                    return json.load(f)
            return {}

        # Sumar dos tiempos tipo HH:MM:SS
        def sumar_tiempos(t1, t2):
            fmt = "%H:%M:%S"
            d1 = datetime.strptime(t1, fmt)
            d2 = datetime.strptime(t2, fmt)
            suma = (d1 - d1.replace(hour=0)) + (d2 - d2.replace(hour=0))
            return (datetime.min + suma).time().strftime(fmt)

        datos = cargar_datos()

        if lecturista_id not in datos:
            return jsonify({"error": "Lecturista no encontrado"}), 404

        tiempo_total = "00:00:00"
        for tiempo_dia in datos[lecturista_id].values():
            tiempo_total = sumar_tiempos(tiempo_total, tiempo_dia)

        return jsonify({
            "lecturista_id": lecturista_id,
            "tiempo_total": tiempo_total,
            "registro_por_dia": datos[lecturista_id]
        }), 200


    @app.route('/api/registro', methods=['POST'])
    def registrar_login():
        ARCHIVO_JSON = "tiempos_registro.json"  # Ruta del archivo de almacenamiento

        # Obtener el JSON del request
        data = request.get_json()
        if not data:
            return jsonify({"error": "JSON inválido o vacío"}), 400

        login_info = data.get("login")
        envio_info = data.get("envio_registros")

        # Validar estructura del JSON
        if not login_info or not envio_info:
            return jsonify({"error": "Faltan datos: login o envio_registros"}), 400
        if "hora" not in login_info or "user_id" not in login_info or "hora" not in envio_info:
            return jsonify({"error": "Faltan campos requeridos en login o envio_registros"}), 400

        try:
            # Convertir horas desde formato ISO
            hora_login = datetime.fromisoformat(login_info["hora"])
            hora_envio = datetime.fromisoformat(envio_info["hora"])
        except Exception as e:
            return jsonify({"error": f"Formato de hora inválido: {str(e)}"}), 422

        user_id = login_info["user_id"]

        # Calcular tiempo de uso en formato HH:MM:SS
        def calcular_tiempo_uso(inicio, fin):
            duracion = fin - inicio
            horas, resto = divmod(duracion.total_seconds(), 3600)
            minutos, segundos = divmod(resto, 60)
            return f"{int(horas):02}:{int(minutos):02}:{int(segundos):02}"

        # Cargar datos desde archivo (si existe)
        def cargar_datos():
            if os.path.exists(ARCHIVO_JSON):
                try:
                    with open(ARCHIVO_JSON, "r") as f:
                        return json.load(f)
                except Exception:
                    return {}  # Si está corrupto o vacío, lo tratamos como nuevo
            return {}

        # Guardar los datos de vuelta al archivo
        def guardar_datos(data):
            with open(ARCHIVO_JSON, "w") as f:
                json.dump(data, f, indent=4)

        # Sumar dos tiempos en formato HH:MM:SS
        def sumar_tiempos(t1, t2):
            fmt = "%H:%M:%S"
            d1 = datetime.strptime(t1, fmt)
            d2 = datetime.strptime(t2, fmt)
            suma = (d1 - d1.replace(hour=0)) + (d2 - d2.replace(hour=0))
            return (datetime.min + suma).time().strftime(fmt)

        tiempo_str = calcular_tiempo_uso(hora_login, hora_envio)
        fecha_actual = hora_login.date().isoformat()

        datos = cargar_datos()

        if user_id not in datos:
            datos[user_id] = {}

        if fecha_actual in datos[user_id]:
            datos[user_id][fecha_actual] = sumar_tiempos(datos[user_id][fecha_actual], tiempo_str)
        else:
            datos[user_id][fecha_actual] = tiempo_str

        guardar_datos(datos)

        return jsonify({
            "mensaje": "Tiempo registrado",
            "lecturista_id": user_id,
            "fecha": fecha_actual,
            "tiempo_registrado": tiempo_str
        }), 200

    @app.route('/usuarios/nuevo', methods=['POST'])
    def insertar_usuario():
        # Verificar API Key
        api_check = check_api_key()
        if api_check:
            return api_check

        data = request.json
        # Verifica que vengan todos los campos requeridos
        requeridos = [
            "calDescripcion",
            "calUsuario",
            "calPassword",
            "calActivo",
            "calAdministrador"
        ]
        if not all(campo in data for campo in requeridos):
            return jsonify({'error': 'Faltan campos obligatorios'}), 400

        driver = '{SQL Server}'
        connection_string = f'DRIVER={driver};SERVER={db_config["server"]};DATABASE={db_config["database"]};UID={db_config["username"]};PWD={db_config["password"]}'

        try:
            conn = pyodbc.connect(connection_string)
            cursor = conn.cursor()

            # Inserción sin calId (se asume que es autoincremental)
            query = """
                INSERT INTO [DBAGUA].[dbo].[Cat_Lecturistas] 
                (calDescripcion, calUsuario, calPassword, calActivo, calAdministrador)
                VALUES (?, ?, ?, ?, ?)
            """
            cursor.execute(query, (
                data["calDescripcion"],
                data["calUsuario"],
                data["calPassword"],
                data["calActivo"],
                data["calAdministrador"]
            ))
            conn.commit()

            return jsonify({'message': 'Usuario insertado correctamente'}), 201

        except Exception as e:
            return jsonify({'error': f'Error de base de datos: {str(e)}'}), 500


    @app.route('/cambiar_contraseña', methods=['POST'])
    def cambiar_contraseña():
        # Verificar API Key
        api_check = check_api_key()
        if api_check:
            return api_check
        
        data = request.json
        username = data.get('username')
        old_password = data.get('old_password')
        new_password = data.get('new_password')

        # Validaciones
        if not username or not old_password or not new_password:
            return jsonify({'error': 'Se requieren los campos: username, old_password, new_password'}), 400

        # Conexión a la base de datos
        driver = '{SQL Server}'
        connection_string = f'DRIVER={driver};SERVER={db_config["server"]};DATABASE={db_config["database"]};UID={db_config["username"]};PWD={db_config["password"]}'
        
        try:
            conn = pyodbc.connect(connection_string)
            cursor = conn.cursor()

            # Consultar el usuario y la contraseña almacenada
            query = "SELECT [calPassword], [calId] FROM [DBAGUA].[dbo].[Cat_Lecturistas] WHERE [calUsuario] = ?"
            cursor.execute(query, (username,))
            
            row = cursor.fetchone()
            if not row:
                return jsonify({'error': 'Usuario no encontrado'}), 404
            
            stored_password = row[0]
            id_lecturista = row[1]

            # Verificar si la contraseña antigua es correcta
            if old_password != stored_password:
                return jsonify({'error': 'La contraseña actual es incorrecta'}), 401
            
            # Actualizar la contraseña
            update_query = "UPDATE [DBAGUA].[dbo].[Cat_Lecturistas] SET [calPassword] = ? WHERE [calId] = ?"
            cursor.execute(update_query, (new_password, id_lecturista))
            conn.commit()
            
            conn.close()
            
            return jsonify({'message': 'Contraseña actualizada exitosamente'}), 200

        except Exception as e:
            return jsonify({'error': f'Error de base de datos: {str(e)}'}), 500

    
    @app.route('/login', methods=['POST'])
    def login():
        # Verificar API Key
        api_check = check_api_key()
        if api_check:
            return api_check
        
        data = request.json
        username = data.get('username')
        password = data.get('password')

        if not username or not password:
            return jsonify({'error': 'Se requiere nombre de usuario y contraseña'}), 400

        # Create connection string
        driver = '{SQL Server}'
        connection_string = f'DRIVER={driver};SERVER={db_config["server"]};DATABASE={db_config["database"]};UID={db_config["username"]};PWD={db_config["password"]}'
        
        try:
            # Connect to database
            conn = pyodbc.connect(connection_string)
            cursor = conn.cursor()
            
            # Query user
            query = "SELECT [calPassword], [calId] FROM [DBAGUA].[dbo].[Cat_Lecturistas] WHERE [calUsuario] = ?"
            cursor.execute(query, (username,))
            
            row = cursor.fetchone()
            if not row:
                return jsonify({'error': 'Usuario no encontrado'}), 404
                
            stored_password = row[0]
            id_lecturista = row[1]
            
            # Verify password
            if password == stored_password:
                return jsonify({
                    'access': 1,
                    'id': str(id_lecturista)
                }), 200
            else:
                return jsonify({'error': 'Contraseña incorrecta'}), 401
                
        except Exception as e:
            return jsonify({'error': f'Error de base de datos: {str(e)}'}), 500

    @app.route('/Incidencia', methods=['POST'])
    def mostrar_incidencias():
        # Verificar API Key
        api_check = check_api_key()
        if api_check:
            return api_check
        
        try:
            # Create connection string
            driver = '{SQL Server}'
            connection_string = f'DRIVER={driver};SERVER={db_config["server"]};DATABASE={db_config["database"]};UID={db_config["username"]};PWD={db_config["password"]}'
            
            # Connect to database
            conn = pyodbc.connect(connection_string)
            cursor = conn.cursor()
            
            # Query incidencias
            query = "SELECT * FROM Cat_Incidencias"
            cursor.execute(query)
            
            # Fetch all rows
            rows = cursor.fetchall()
            
            # Format data
            formatted_data = []
            for row in rows:
                entry = {
                    "id": row[0],
                    "description": row[1],
                    "status": row[2],
                    "value": row[3]
                }
                formatted_data.append(entry)
                
            return jsonify(formatted_data), 200
            
        except Exception as e:
            return jsonify({'error': f'Error de base de datos: {str(e)}'}), 500
    @app.route('/usuarios/campos', methods=['GET'])
    def obtener_nombres_campos():
        # Verificar API Key
        api_check = check_api_key()
        if api_check:
            return api_check

        driver = '{SQL Server}'
        connection_string = f'DRIVER={driver};SERVER={db_config["server"]};DATABASE={db_config["database"]};UID={db_config["username"]};PWD={db_config["password"]}'

        try:
            conn = pyodbc.connect(connection_string)
            cursor = conn.cursor()

            # Traer solo la descripción de las columnas
            cursor.execute("SELECT TOP 1 * FROM [DBAGUA].[dbo].[Cat_Lecturistas]")
            columnas = [col[0] for col in cursor.description]

            return jsonify({'campos': columnas}), 200

        except Exception as e:
            return jsonify({'error': f'Error de base de datos: {str(e)}'}), 500

    @app.route('/api/consumo/actualizar', methods=['POST'])
    def actualizar_consumo():
        # Verificar API Key
        api_check = check_api_key()
        if api_check:
            return api_check
        
        driver = '{SQL Server}'
        print("""#=-=-=-=-=-=-=-=-=- Actualizando consumo =-=-=-=-=-=-=-=-=-#""")
        
        # Recibir los datos del cliente
        datos = request.json
        # Obtener todos los valores del JSON recibido
        id = datos.get('id')
        user_id = datos.get('user_id')
        contrato = datos.get('paaNumeroContrato')
        idcolonia = datos.get('paaIdColonia')
        lectura = datos.get('paaLecturaActual')
        bimestre = datos.get('paaBimestre')
        observacion = datos.get("paaObservacion")
        fecha = datos.get("Fecha")
        cord = datos.get("paaCoordenadas")
        idanomalia = datos.get("paaIdAnomalia")
        img = datos.get('imagen_medidor')
        img_fachada = datos.get('IMG_Fachada')

        # Imprimir todas las claves recibidas
        for key, value in datos.items():
            # Si es imagen, solo imprime si existe (yes/no)
            if key in ['imagen_medidor', 'IMG_Fachada']:
                print(f"- {key}: {'yes' if value else 'no'}")
            else:
                print(f"- {key}: {value}")


        # Validar que los datos requeridos estén presentes
        if not contrato or lectura is None:
            return jsonify({'error': 'Se requiere contrato y lectura'}), 400

        # Consultar los datos actuales de la cuenta
        connection_string = f'DRIVER={driver};SERVER={db_config["server"]};DATABASE={db_config["database"]};UID={db_config["username"]};PWD={db_config["password"]}'
        try:
            conn = pyodbc.connect(connection_string)
            cursor = conn.cursor()
            
            cursor.execute("""
                SELECT paaUltimaLectura, paaLecturaActual, paaConsumo, paaNombres, paaClaveUsuario
                FROM PadronUsuariosAgua
                WHERE paaNumeroContrato = ?
            """, (contrato,))
            
            datos_actuales = cursor.fetchone()

            # Validar si los datos del contrato existen
            if not datos_actuales:
                return jsonify({'error': 'No se encontró el contrato especificado'}), 404

            # Procesar los datos obtenidos
            ultima_lectura, lectura_anterior, consumo_anterior, paaNombres, paaClaveUsuario = datos_actuales
            print(f"clave actualizada: {paaClaveUsuario}")
            
            # Convertir valores a float y validar
            ultima_lectura = float(ultima_lectura) if str(ultima_lectura).strip().replace('.', '', 1).isdigit() else 0
            lectura_anterior = float(lectura_anterior) if str(lectura_anterior).strip().replace('.', '', 1).isdigit() else 0
            consumo_anterior = float(consumo_anterior) if str(consumo_anterior).strip().replace('.', '', 1).isdigit() else 0
        except Exception as e:
            print(f"Error de base de datos: {e}")
            return jsonify({
                "error": f"Error de base de datos: {e}"
            }), 500

        # Calcular el nuevo consumo
        consumo_nuevo = float(lectura) - ultima_lectura

        # Validar que el consumo sea positivo
        if consumo_nuevo < 0:
            return jsonify({'error': 'La lectura actual no puede ser menor que la última registrada'}), 400

        # Ejecutar Procedimiento almacenado Lecturas_Insert
        try:
            conn = pyodbc.connect(connection_string)
            cursor = conn.cursor()
            
            
            
            # Obtener ID de incidencia
            query = """
            SELECT incIdIncidencia
            FROM dbo.Cat_Incidencias
            WHERE incDescripcion = ?
            """
            cursor.execute(query, (idanomalia,))
            resultado = cursor.fetchone()
            incidencia_id = resultado[0] if resultado else 0
            
            # Preparar valores para el procedimiento almacenado
            valores = (
                str(paaClaveUsuario),
                int(datetime.datetime.now().year),
                int(bimestre),
                float(consumo_nuevo),
                float(lectura),
                float(ultima_lectura),
                incidencia_id,
                int(id),
                str(user_id),
                str(f'observacion movil:{observacion}')
            )

           
            cursor.execute("{CALL dbo.Lecturas_Insert (?,?,?,?,?,?,?,?,?,?)}", valores)
            conn.commit()

            return jsonify({"message": "¡Lectura ingresada!"}), 200
        except Exception as e:
            print(f"Error al insertar lectura: {e}")
            return jsonify({"error": f"Error al insertar lectura: {e}"}), 500
