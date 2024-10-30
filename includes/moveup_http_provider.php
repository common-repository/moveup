<?php
if ( ! class_exists( 'MoveUpWPHttpProvider' ) ) {
    class MoveUpWPHttpProvider {
        static $host;
        static $timeout = 60;

        public static function remote_put( $url, array $data, $token = "" ) {
            $response = [];
            try {
                $args = [
                    'method'    => 'PUT',
                    'timeout'   => self::$timeout,
                    'sslverify' => false,
                    'headers'   => [
                        'Authorization' => "Bearer {$token}",
                        'Content-Type'  => 'application/json',
                    ],
                    'body'      => json_encode( $data ),
                ];

                $request = wp_remote_request( $url, $args );

                if ( ! is_wp_error( $request ) ) {
                    $response['code'] = wp_remote_retrieve_response_code( $request );
                    $response['data'] = mvn_drop_json_decode( $request['body'] );
                } else {
                    $response['message'] = $request->get_error_message();
                    $response['code']    = $request->get_error_code();
                }

                return $response;
            } catch ( Exception $ex ) {
                $response['data']    = [];
                $response['message'] = $ex->getMessage();
                $response['code']    = 400;
            }

            return $response;
        }
    }
}