<? php
// Dataseo inc
// www.dataseoinc.com
	
	
classe  Chromecast
{
	// Envia uma foto ou um vídeo para um Chromecast usando reverso
	// protocolo castV2 projetado
	 soquete público $ ;
	// Soquete para o Chromecast
	public  $ requestId  =  1 ;
	// Incrementando parâmetro do ID da solicitação
	public  $ transportid  =  " " ;
	// O transporte da nossa conexão
	public  $ sessionid  =  " " ;
	// ID da sessão para qualquer sessão de mídia
	público  $ DMP ;
	// Representa uma instância do Default Media Player.
	público  $ Plex ;
	// Representa uma instância do player Plex
	public  $ lastip  =  " " ;
	// Armazena o último IP conectado
	public  $ lastport ;
	// Armazena a última porta conectada
	public  $ lastactivetime ;
	// armazena a última vez que fizemos algo
	
	 função  pública __construct ( $ ip , $ port )
	{
		// Estabelecer conexão com o Chromecast
		// Não preste muita atenção ao certificado do Chromecast.
		// Será o endereço do host errado de qualquer maneira, se
		// use encaminhamento de porta!
		$ contextOptions  = [ ' ssl '  => [ ' verificar_peer '  =>  falso , ' verificar_peer_name '  =>  falso ,]];
		$ context  =  stream_context_create ( $ contextOptions );
		if ( $ this -> socket  =  stream_socket_client ( ' ssl: // '  .  $ ip  .  " : "  .  $ port , $ errno , $ errstr , 30 , STREAM_CLIENT_CONNECT , $ context )) {
		}
		mais {
			lançar  nova  exceção ( " Falha ao conectar-se ao Chromecast remoto " );
		}
		$ this -> lastip  =  $ ip ;
		$ this -> lastport  =  $ port ;
		$ this -> lastactivetime  =  time ();
		// Crie uma instância do DMP para este CCDefaultMediaPlayer
		$ this -> DMP  =  new  CCDefaultMediaPlayer ( $ this );
		$ this -> Plex  =  novo  CCPlexPlayer ( $ this );
	}
	
	 verificação de função estática  pública ( $ wait = 15 )   
	{
		// Wrapper para verificação
		$ resultado  =  Chromecast :: scansub ( $ espera );
		retornar  $ resultado ;
	}
	
	 função estática  pública scansub ( $ wait = 15 )   
	{
		// Executa uma varredura mdns da rede para encontrar chromecasts e retorna uma matriz
		// Vamos testar encontrando o Google Chromecasts
		$ mdns  =  novo  mDNS ();
		// Pesquise dispositivos chromecast
		// Para garantir um pouco mais, envie várias solicitações de pesquisa
		$ firstresponsetime  =  -  1 ;
		$ lastpackettime  =  -  1 ;
		$ starttime  =  round ( microtime ( true ) *  1000 );
		$ mdns -> query ( " _googlecast._tcp.local " , 1 , 12 , " " );
		$ mdns -> query ( " _googlecast._tcp.local " , 1 , 12 , " " );
		$ mdns -> query ( " _googlecast._tcp.local " , 1 , 12 , " " );
		$ cc  =  $ espera ;
		$ filetoget  =  1 ;
		$ dontrequery  =  0 ;
		set_time_limit ( $ wait  *  2 );
		$ chromecasts  =  array ();
		while ( $ cc  >  0 ) {
			$ inpacket  =  " " ;
			while ( $ inpacket  ==  " " ) {
				$ inpacket  =  $ mdns -> readIncoming ();
				if ( $ inpacket  <>  " " ) {
					if ( $ inpacket -> packetheader -> getQuestions () >  0 ) {
						$ inpacket  =  " " ;
					}
				}
				if ( $ lastpackettime  <>  -  1 ) {
					// Se chegarmos aqui, temos um último horário válido
					$ timesincelastpacket  =  round ( microtime ( true ) *  1000 ) -  $ lastpackettime ;
					if ( $ timesincelastpacket  > ( $ firstresponsetime  *  5 ) &&  $ firstresponsetime  ! =  -  1 ) {
						retornar  $ chromecasts ;
					}
				}
				if ( $ inpacket  <>  " " ) {
					$ lastpackettime  =  round ( microtime ( true ) *  1000 );
				}
				$ timetohere  =  round ( microtime ( true ) *  1000 ) -  $ horário de início ;
				// Regra máxima de cinco segundos
				if ( $ timetoaqui  >  5000 ) {
					retornar  $ chromecasts ;
				}
			}
			// Se nosso pacote tiver respostas, leia-as
			// $ mdns-> printPacket ($ inpacket);
			if ( $ inpacket -> packetheader -> getAnswerRRs () >  0 ) {
				$ dontrequery  =  0 ;
				// $ mdns-> printPacket ($ inpacket);
				for ( $ x  =  0 ; $ x  <  sizeof ( $ inpacket -> answerrrs ); $ x ++ ) {
					if ( $ inpacket -> answerrrs [ $ x ] -> qtype  ==  12 ) {
						// print_r ($ inpacket-> answerrrs [$ x]);
						if ( $ inpacket -> answerrrs [ $ x ] -> name  ==  " _googlecast._tcp.local " ) {
							if ( $ firstresponsetime  ==  -  1 ) {
								$ firstresponsetime  =  round ( microtime ( true ) *  1000 ) -  $ starttime ;
							}
							$ name  =  " " ;
							para ( $ y  =  0 ; $ y  <  sizeof ( $ inpacket -> answerrrs [ $ x ] -> data ); $ y ++ ) {
								$ name . =  chr ( $ inpacket -> answerrrs [ $ x ] -> dados [ $ y ]);
							}
							// O próprio chromecast preenche rrs adicionais. Então, se estiver lá, temos um método mais rápido de
							// processando os resultados.
							// Primeiro crie quaisquer entradas ausentes com quaisquer 33 pacotes que encontrarmos.
							for ( $ p  =  0 ; $ p  <  sizeof ( $ inpacket -> additionalrrs ); $ p ++ ) {
								if ( $ inpacket -> additionalrrs [ $ p ] -> qtype  ==  33 ) {
									$ d  =  $ inpacket -> additionalrrs [ $ p ] -> dados ;
									$ port  = ( $ d [ 4 ] *  256 ) +  $ d [ 5 ];
									// Precisamos do alvo dos dados
									$ deslocamento  =  6 ;
									$ tamanho  =  $ d [ $ deslocamento ];
									$ offset ++ ;
									$ target  =  " " ;
									para ( $ z  =  0 ; $ z  <  $ tamanho ; $ z ++ ) {
										$ target . =  chr ( $ d [ $ offset  +  $ z ]);
									}
									$ target . =  " .local " ;
									if ( ! isset ( $ chromecasts [ $ inpacket -> additionalrrs [ $ p ] -> name ])) {
										$ chromecasts [ $ inpacket -> additionalrrs [ $ x ] -> name ] =  matriz (
											" port "  =>  $ port ,
											" ip "  =>  " " ,
											" target "  =>  " " ,
											" friendlyname "  =>  " "
										);
									}
									$ chromecasts [ $ inpacket -> additionalrrs [ $ x ] -> name ] [ ' target ' ] =  $ target ;
								}
							}
							// Em seguida, repita o processo para 16
							for ( $ p  =  0 ; $ p  <  sizeof ( $ inpacket -> additionalrrs ); $ p ++ ) {
								if ( $ inpacket -> additionalrrs [ $ p ] -> qtype  ==  16 ) {
									$ fn  =  " " ;
									para ( $ q  =  0 ; $ q  <  sizeof ( $ inpacket -> additionalrrs [ $ p ] -> data ); $ q ++ ) {
										$ fn . =  chr ( $ inpacket -> adicionalrrs [ $ p ] -> dados [ $ q ]);
									}
									$ stp  =  strpos ( $ fn , " fn = " ) +  3 ;
									$ etp  =  strpos ( $ fn , " ca = " );
									$ fn  =  substr ( $ fn , $ stp , $ etp  -  $ stp  -  1 );
									if ( ! isset ( $ chromecasts [ $ inpacket -> additionalrrs [ $ p ] -> name ])) {
										$ chromecasts [ $ inpacket -> additionalrrs [ $ x ] -> name ] =  matriz (
											" port "  =>  8009 ,
											" ip "  =>  " " ,
											" target "  =>  " " ,
											" friendlyname "  =>  " "
										);
									}
									$ chromecasts [ $ inpacket -> additionalrrs [ $ x ] -> name ] [ ' nome amigável ' ] =  $ fn ;
								}
							}
							// E finalmente repita novamente por 1
							for ( $ p  =  0 ; $ p  <  sizeof ( $ inpacket -> additionalrrs ); $ p ++ ) {
								if ( $ inpacket -> additionalrrs [ $ p ] -> qtype  ==  1 ) {
									$ d  =  $ inpacket -> additionalrrs [ $ p ] -> dados ;
									$ ip  =  $ d [ 0 ] .  " "  .  $ d [ 1 ] .  " "  .  $ d [ 2 ] .  " "  .  $ d [ 3 ];
									foreach ( $ chromecasts  como  $ key  =>  $ value ) {
										if ( $ value [ ' target ' ] ==  $ inpacket -> additionalrrs [ $ p ] -> name ) {
											$ value [ ' ip ' ] =  $ ip ;
											$ chromecasts [ $ key ] =  $ value ;
										}
									}
								}
							}
							$ dontrequery  =  1 ;
							// Confira nosso item. Se não existir, não estava nos adicionais, então envie solicitações.
							// Se existir, verifique se possui todos os itens. Caso contrário, envie as solicitações.
							if ( isset ( $ chromecasts [ $ name ])) {
								$ xx  =  $ chromecasts [ $ name ];
								if ( $ xx [ ' target ' ] ==  " " ) {
									// Enviar uma solicitação 33
									$ mdns -> query ( $ name , 1 , 33 , " " );
									$ dontrequery  =  0 ;
								}
								if ( $ xx [ 'nome amigável ' ] ==  " " ) {
									// Envie uma solicitação 16
									$ mdns -> query ( $ name , 1 , 16 , " " );
									$ dontrequery  =  0 ;
								}
								if ( $ xx [ ' target ' ] ! =  " "  &&  $ xx [ ' friendlyname ' ] ! =  " "  &&  $ xx [ ' ip ' ] ==  " " ) {
									// Faltando apenas o endereço IP do destino.
									$ mdns -> query ( $ xx [ ' target ' ], 1 , 1 , " " );
									$ dontrequery  =  0 ;
								}
							}
							mais {
								// Envie consultas. Isso acionará uma consulta 1 quando tivermos um nome de destino.
								$ mdns -> query ( $ name , 1 , 33 , " " );
								$ mdns -> query ( $ name , 1 , 16 , " " );
								$ dontrequery  =  0 ;
							}
							if ( $ dontrequery  ==  0 ) {
								$ cc  =  $ espera ;
							}
							set_time_limit ( $ wait  *  2 );
						}
					}
					if ( $ inpacket -> answerrrs [ $ x ] -> qtype  ==  33 ) {
						$ d  =  $ inpacket -> answerrrs [ $ x ] -> dados ;
						$ port  = ( $ d [ 4 ] *  256 ) +  $ d [ 5 ];
						// Precisamos do alvo dos dados
						$ deslocamento  =  6 ;
						$ tamanho  =  $ d [ $ deslocamento ];
						$ offset ++ ;
						$ target  =  " " ;
						para ( $ z  =  0 ; $ z  <  $ tamanho ; $ z ++ ) {
							$ target . =  chr ( $ d [ $ offset  +  $ z ]);
						}
						$ target . =  " .local " ;
						if ( ! isset ( $ chromecasts [ $ inpacket -> answerrrs [ $ x ] -> name ])) {
							$ chromecasts [ $ inpacket -> answerrrs [ $ x ] -> name ] =  matriz (
								" port "  =>  $ port ,
								" ip "  =>  " " ,
								" target "  =>  $ target ,
								" friendlyname "  =>  " "
							);
						}
						mais {
							$ chromecasts [ $ inpacket -> answerrrs [ $ x ] -> name ] [ ' target ' ] =  $ target ;
						}
						// Sabemos o nome e a porta. Enviar uma consulta A para o endereço IP
						$ mdns -> query ( $ target , 1 , 1 , " " );
						$ cc  =  $ espera ;
						set_time_limit ( $ wait  *  2 );
					}
					if ( $ inpacket -> answerrrs [ $ x ] -> qtype  ==  16 ) {
						$ fn  =  " " ;
						para ( $ q  =  0 ; $ q  <  sizeof ( $ inpacket -> answerrrs [ $ x ] -> data ); $ q ++ ) {
							$ fn . =  chr ( $ inpacket -> answerrrs [ $ x ] -> dados [ $ q ]);
						}
						$ stp  =  strpos ( $ fn , " fn = " ) +  3 ;
						$ etp  =  strpos ( $ fn , " ca = " );
						$ fn  =  substr ( $ fn , $ stp , $ etp  -  $ stp  -  1 );
						if ( ! isset ( $ chromecasts [ $ inpacket -> answerrrs [ $ x ] -> name ])) {
							$ chromecasts [ $ inpacket -> answerrrs [ $ x ] -> name ] =  matriz (
								" port "  =>  8009 ,
								" ip "  =>  " " ,
								" target "  =>  " " ,
								" friendlyname "  =>  $ fn
							);
						}
						mais {
							$ chromecasts [ $ inpacket -> answerrrs [ $ x ] -> name ] [ ' nome amigável ' ] =  $ fn ;
						}
						$ mdns -> query ( $ chromecasts [ $ inpacket -> answerrrs [ $ x ] -> name ] [ ' target ' ], 1 , 1 , " " );
						$ cc  =  $ espera ;
						set_time_limit ( $ wait  *  2 );
					}
					if ( $ inpacket -> answerrrs [ $ x ] -> qtype  ==  1 ) {
						$ d  =  $ inpacket -> answerrrs [ $ x ] -> dados ;
						$ ip  =  $ d [ 0 ] .  " "  .  $ d [ 1 ] .  " "  .  $ d [ 2 ] .  " "  .  $ d [ 3 ];
						// Passa pelos chromecasts e preenche o ip
						foreach ( $ chromecasts  como  $ key  =>  $ value ) {
							if ( $ value [ ' target ' ] ==  $ inpacket -> answerrrs [ $ x ] -> name ) {
								$ value [ ' ip ' ] =  $ ip ;
								$ chromecasts [ $ key ] =  $ value ;
								// Se tivermos um endereço IP, mas não houver nome amigável, tente obter o nome amigável novamente!
								if ( strlen ( $ value [ ' friendlyname ' ]) <  1 ) {
									$ mdns -> query ( chave $ , 1 , 16 , " " );
									$ cc  =  $ espera ;
									set_time_limit ( $ wait  *  2 );
								}
							}
						}
					}
				}
			}
			$ cc - ;
		}
		retornar  $ chromecasts ;
	}
	
	função  testLive ()
	{
		// Se houver uma diferença de 10 segundos ou mais entre $ this-> lastactivetime e o horário atual, teremos iniciado e precisamos reconectar
		if ( $ this -> lastip  ==  " " ) {
			retorno ;
		}
		$ diff  =  time () -  $ this -> lastactivetime ;
		if ( $ diff  >  9 ) {
			// Reconectar
			$ contextOptions  = [ ' ssl '  => [ ' verificar_peer '  =>  falso , ' verificar_peer_name '  =>  falso ,]];
			$ context  =  stream_context_create ( $ contextOptions );
			if ( $ this -> socket  =  stream_socket_client ( ' ssl: // '  .  $ this -> lastip  .  " : "  .  $ this -> lastport , $ errno , $ errstr , 30 , STREAM_CLIENT_CONNECT , $ context )) {
			}
			mais {
				lançar  nova  exceção ( " Falha ao conectar-se ao Chromecast remoto " );
			}
			$ this -> cc_connect ( 1 );
			$ this -> connect ( 1 );
		}
	}
	
	função  cc_connect ( $ tl  =  0 )
	{
		// CONEXÃO AO CROMECAST
		// Isso se conecta ao chromecast em geral.
		// Geralmente, isso é chamado pelo lançamento ($ appid) automaticamente ao iniciar um aplicativo
		// mas se você deseja se conectar a um aplicativo em execução existente, chame isso primeiro,
		//, em seguida, chame getStatus () para garantir que você receba um transporte.
		if ( $ tl  ==  0 ) {
			$ this -> testLive ();
		};
		$ c  =  new  CastMessage ();
		$ c -> source_id  =  " remetente-0 " ;
		$ c -> receiver_id  =  " receptor-0 " ;
		$ c -> urnnamespace  =  " urn: x-cast: com.google.cast.tp.connection " ;
		$ c -> payloadtype  =  0 ;
		$ c -> payloadutf8  =  ' {"tipo": "CONNECT"} ' ;
		fwrite ( $ this -> socket , $ c -> encode ());
		fflush ( $ this -> socket );
		$ this -> lastactivetime  =  time ();
	}
	
	 lançamento da função  pública ( $ appid )
	{
		// Inicia o aplicativo chromecast no chromecast conectado
		// CONNECT
		$ this -> cc_connect ();
		$ this -> getStatus ();
		// LANÇAMENTO
		$ c  =  new  CastMessage ();
		$ c -> source_id  =  " remetente-0 " ;
		$ c -> receiver_id  =  " receptor-0 " ;
		$ c -> urnnamespace  =  " urn: x-cast: com.google.cast.receiver " ;
		$ c -> payloadtype  =  0 ;
		$ c -> payloadutf8  =  ' {"type": "LANÇAMENTO", "appId": " '  .  $ appid  .  ' ", "requestId": '  .  $ this -> requestId  .  ' } ' ;
		fwrite ( $ this -> socket , $ c -> encode ());
		fflush ( $ this -> socket );
		$ this -> lastactivetime  =  time ();
		$ this -> requestId ++ ;
		$ oldtransportid  =  $ this -> transportid ;
		while ( $ this -> transportid  ==  " "  ||  $ this -> transportid  ==  $ oldtransportid ) {
			$ r  =  $ this -> getCastMessage ();
			sono ( 1 );
		}
	}
	
	função  getStatus ()
	{
		// Obtenha o status do chromecast em geral e devolva-o
		// também preenche o transportId de qualquer aplicativo em execução no momento
		$ this -> testLive ();
		$ c  =  new  CastMessage ();
		$ c -> source_id  =  " remetente-0 " ;
		$ c -> receiver_id  =  " receptor-0 " ;
		$ c -> urnnamespace  =  " urn: x-cast: com.google.cast.receiver " ;
		$ c -> payloadtype  =  0 ;
		$ c -> payloadutf8  =  ' {"tipo": "GET_STATUS", "requestId": '  .  $ this -> requestId  .  ' } ' ;
		$ c  =  fwrite ( $ this -> socket , $ c -> encode ());
		fflush ( $ this -> socket );
		$ this -> lastactivetime  =  time ();
		$ this -> requestId ++ ;
		$ r  =  " " ;
		while ( $ this -> transportid  ==  " " ) {
			$ r  =  $ this -> getCastMessage ();
		}
		retornar  $ r ;
	}
	
	função  connect ( $ tl  =  0 )
	{
		// Isso se conecta ao transporte do aplicativo em execução no momento
		// (você precisa ter iniciado ou conectado e ter o status)
		if ( $ tl  ==  0 ) {
			$ this -> testLive ();
		};
		$ c  =  new  CastMessage ();
		$ c -> source_id  =  " remetente-0 " ;
		$ c -> receiver_id  =  $ this -> transportid ;
		$ c -> urnnamespace  =  " urn: x-cast: com.google.cast.tp.connection " ;
		$ c -> payloadtype  =  0 ;
		$ c -> payloadutf8  =  ' {"tipo": "CONNECT"} ' ;
		fwrite ( $ this -> socket , $ c -> encode ());
		fflush ( $ this -> socket );
		$ this -> lastactivetime  =  time ();
		$ this -> requestId ++ ;
	}
	
	 função  pública getCastMessage ()
	{
		// Obter a mensagem / resposta do Chromecast
		// Mais tarde, poderíamos atualizar o CCprotoBuf para decodificar isso
		// mas por enquanto tudo o que precisamos é o ID de transporte e o ID da sessão, se for
		// no pacote e podemos ler isso diretamente.
		$ this -> testLive ();
		$ response  =  fread ( $ this -> socket , 2000 );
		while ( preg_match ( "/ urn: x-cast: com.google.cast.tp.heartbeat /" , $ response ) &&  preg_match ( "/ \" PING \ " /" , $ response )) {
			$ this -> pong ();
			sono ( 3 );
			$ response  =  fread ( $ this -> socket , 2000 );
			// Aguarde infinitamente por um pacote.
			set_time_limit ( 30 );
		}
		if ( preg_match ( "/ transportId / s" , $ response )) {
			preg_match ( "/ transportId \" \: \ " ( [^ \" ] * ) / " , $ response , $ correspondências );
			$ partidas  =  $ partidas [ 1 ];
			$ this -> transportid  =  $ correspondências ;
		}
		if ( preg_match ( "/ sessionId / s" , $ response )) {
			preg_match ( "/ \" sessionId \ " \: \" ( [^ \ " ] * ) /" , $ response , $ r );
			$ this -> sessionid  =  $ r [ 1 ];
		}
		retornar  $ resposta ;
	}
	
	 função  pública sendMessage ( $ urn , $ message )
	{
		// Envia a mensagem fornecida para a urna especificada
		$ this -> testLive ();
		$ c  =  new  CastMessage ();
		$ c -> source_id  =  " remetente-0 " ;
		$ c -> receiver_id  =  $ this -> transportid ;
		// Substituir - se o $ urn for urn: x-cast: com.google.cast.receiver,
		// envia para o receptor-0 e não o aplicativo em execução
		if ( $ urn  ==  " urn: x-cast: com.google.cast.receiver " ) {
			$ c -> receiver_id  =  " receptor-0 " ;
		}
		if ( $ urn  ==  " urn: x-cast: com.google.cast.tp.connection " ) {
			$ c -> receiver_id  =  " receptor-0 " ;
		}
		$ c -> urnnamespace  =  $ urn ;
		$ c -> payloadtype  =  0 ;
		$ c -> payloadutf8  =  $ mensagem ;
		fwrite ( $ this -> socket , $ c -> encode ());
		fflush ( $ this -> socket );
		$ this -> lastactivetime  =  time ();
		$ this -> requestId ++ ;
		$ resposta  =  $ this -> getCastMessage ();
		retornar  $ resposta ;
	}
	
	 função  pública pingpong ()
	{
		// Oficialmente, você deve executar isso a cada 5 segundos ou mais para manter
		// o dispositivo vivo. Não parece ser necessário se um aplicativo estiver sendo executado
		// que não tem um tempo limite curto.
		$ c  =  new  CastMessage ();
		$ c -> source_id  =  " remetente-0 " ;
		$ c -> receiver_id  =  " receptor-0 " ;
		$ c -> urnnamespace  =  " urn: x-cast: com.google.cast.tp.heartbeat " ;
		$ c -> payloadtype  =  0 ;
		$ c -> payloadutf8  =  ' {"tipo": "PING"} ' ;
		fwrite ( $ this -> socket , $ c -> encode ());
		fflush ( $ this -> socket );
		$ this -> lastactivetime  =  time ();
		$ this -> requestId ++ ;
		$ resposta  =  $ this -> getCastMessage ();
	}
	
	 função  pública pong ()
	{
		// Para responder um pingue-pongue
		$ c  =  new  CastMessage ();
		$ c -> source_id  =  " remetente-0 " ;
		$ c -> receiver_id  =  " receptor-0 " ;
		$ c -> urnnamespace  =  " urn: x-cast: com.google.cast.tp.heartbeat " ;
		$ c -> payloadtype  =  0 ;
		$ c -> payloadutf8  =  ' {"tipo": "PONG"} ' ;
		fwrite ( $ this -> socket , $ c -> encode ());
		fflush ( $ this -> socket );
		$ this -> lastactivetime  =  time ();
		$ this -> requestId ++ ;
	}
}
? >
