<?php

class FPDF_HTML extends FPDF {

	var $B = 0;
	var $I = 0;
	var $U = 0;
	var $HREF = '';
	var $ALIGN = '';

	function WriteHTML( $html ) {
		//HTML parser
		$html = str_replace( "\n", ' ', $html );
		$a = preg_split( '/<(.*)>/U', $html, -1, PREG_SPLIT_DELIM_CAPTURE );
		foreach ( $a as $i => $e ) {
			if ( $i % 2 == 0 ) {
				//Text
				if ( $this->HREF ) {
					$this->PutLink( $this->HREF, $e );
				} elseif ( $this->ALIGN == 'center' ) {
					$this->Cell( 0, 5, $e, 0, 1, 'C' );
				} else {
					$this->Write( 5, $e );
				}
			} else {
				//Tag
				if ( $e[0] == '/' ) {
					$this->CloseTag( strtoupper( substr( $e, 1 ) ) );
				} else {
					//Extract properties
					$a2 = explode( ' ', $e );
					$tag = strtoupper( array_shift( $a2 ) );
					$prop = array();
					foreach ( $a2 as $v ) {
						if ( preg_match( '/([^=]*)=["\']?([^"\']*)/', $v, $a3 ) ) {
							$prop[strtoupper( $a3[1] )] = $a3[2];
						}
					}
					$this->OpenTag( $tag, $prop );
				}
			}
		}

	}

	function OpenTag( $tag, $prop ) {
		//Opening tag
		if ( $tag == 'B' || $tag == 'I' || $tag == 'U' ) {
			$this->SetStyle( $tag, true );
		}
		if ( $tag == 'A' ) {
			$this->HREF = $prop['HREF'];
		}
		if ( $tag == 'BR' ) {
			$this->Ln( 5 );
		}
		if ( $tag == 'P' ) {
			$this->ALIGN = $prop['ALIGN'];
		}
		if ( $tag == 'HR' ) {
			if ( ! empty( $prop['WIDTH'] ) ) {
				$Width = $prop['WIDTH'];
			} else {
				$Width = $this->w - $this->lMargin - $this->rMargin;
				$this->Ln( 2 );
				$x = $this->GetX();
				$y = $this->GetY();
				$this->SetLineWidth( 0.4 );
				$this->Line( $x, $y, $x + $Width, $y );
				$this->SetLineWidth( 0.2 );
				$this->Ln( 2 );
			}
		}

	}

	function CloseTag( $tag ) {
		//Closing tag
		if ( $tag == 'B' || $tag == 'I' || $tag == 'U' ) {
			$this->SetStyle( $tag, false );
		}
		if ( $tag == 'A' ) {
			$this->HREF = '';
		}
		if ( $tag == 'P' ) {
			$this->ALIGN = '';
		}

	}

	function SetStyle( $tag, $enable ) {
		//Modify style and select corresponding font
		$this->$tag+=($enable ? 1 : -1);
		$style = '';
		foreach ( array( 'B', 'I', 'U' ) as $s ) {
			if ( $this->$s > 0 ) {
				$style .= $s;
			}
		}
		$this->SetFont( '', $style );

	}

	function PutLink( $URL, $txt ) {

		//Put a hyperlink
		$this->SetTextColor( 0, 0, 255 );
		$this->SetStyle( 'U', true );
		$this->Write( 5, $txt, $URL );
		$this->SetStyle( 'U', false );
		$this->SetTextColor( 0 );

	}

	/*************************************************************
	 *                                                           *
	 *    MultiCell with bullet (array)                          *
	 *                                                           *
	 *    Requires an array with the following  keys:            *
	 *                                                           *
	 *        Bullet -> String or Number                         *
	 *        Margin -> Number, space between bullet and text    *
	 *        Indent -> Number, width from current x position    *
	 *        Spacer -> Number, calls Cell(x), spacer=x          *
	 *        Text -> Array, items to be bulleted                *
	 *                                                           *
	 * ***********************************************************/

	function MultiCellBltArray( $w, $h, $blt_array, $border = 0, $align = 'J', $fill = 0 ) {
		if ( ! is_array( $blt_array ) ) {
			die( 'MultiCellBltArray requires an array with the following keys: bullet, margin, text, indent, spacer' );
			exit;
		}

		//Save x
		$bak_x = $this->x;

		for ( $i = 0; $i < sizeof( $blt_array['text'] ); $i ++  ) {
			//Get bullet width including margin
			$blt_width = $this->GetStringWidth( $blt_array['bullet'] . $blt_array['margin'] ) + $this->cMargin * 2;

			// SetX
			$this->SetX( $bak_x );

			//Output indent
			if ( $blt_array['indent'] > 0 )
				$this->Cell( $blt_array['indent'] );

			//Output bullet
			$this->Cell( $blt_width, $h, $blt_array['bullet'] . $blt_array['margin'], 0, '', $fill );

			//Output text
			$this->MultiCell( $w - $blt_width, $h, $blt_array['text'][$i], $border, $align, $fill );

			//Insert a spacer between items if not the last item
			if ( $i != sizeof( $blt_array['text'] ) - 1 )
				$this->Ln( $blt_array['spacer'] );

			//Increment bullet if it's a number
			if ( is_numeric( $blt_array['bullet'] ) )
				$blt_array['bullet'] ++;
		}

		//Restore x
		$this->x = $bak_x;

	}

}
