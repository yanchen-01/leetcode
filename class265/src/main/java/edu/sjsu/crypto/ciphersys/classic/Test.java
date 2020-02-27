package edu.sjsu.crypto.ciphersys.classic;

import edu.sjsu.yazdankhah.crypto.util.abstracts.A5_1Abs;
import edu.sjsu.yazdankhah.crypto.util.abstracts.PkzipAbs;
import edu.sjsu.yazdankhah.crypto.util.cipherutils.ConversionUtil;
import edu.sjsu.yazdankhah.crypto.util.primitivedatatypes.Bit;
import edu.sjsu.yazdankhah.crypto.util.primitivedatatypes.UByte;
import edu.sjsu.yazdankhah.crypto.util.primitivedatatypes.Word;
import edu.sjsu.yazdankhah.crypto.util.shiftregisters.LFSR;
import edu.sjsu.yazdankhah.crypto.util.cipherutils.Function;
import edu.sjsu.yazdankhah.crypto.util.cipherutils.StringUtil;

public class Test {

	public static void main(String[] args) {
		String bin_pass = ConversionUtil.textToBinStr("test123456");
		Word test = Word.constructFromBinStr(bin_pass.substring(0,32));
		
		
		test.printBinStr();	//0111 0100 0110 0101 0111 0011 0111 0100
		
		test = test.rightHalfAsWord();	
		
		test.printBinStr(); //0000 0000 0000 0000 0001 0001 0001 0000
		
		//	 test should be?  0000 0000 0000 0000 0111 0011 0111 0100
		
		
	}

}
