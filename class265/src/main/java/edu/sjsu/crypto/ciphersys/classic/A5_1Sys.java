package edu.sjsu.crypto.ciphersys.classic;

import edu.sjsu.yazdankhah.crypto.util.abstracts.A5_1Abs;
import edu.sjsu.yazdankhah.crypto.util.cipherutils.ConversionUtil;
import edu.sjsu.yazdankhah.crypto.util.primitivedatatypes.Bit;
import edu.sjsu.yazdankhah.crypto.util.shiftregisters.LFSR;
import edu.sjsu.yazdankhah.crypto.util.cipherutils.Function;

/**
 * Steam Ciphers A5/1
 * @author sida
   Customer: Yan Chen
 */

public class A5_1Sys extends A5_1Abs {
	protected static LFSR x_register;
	protected static LFSR y_register;
	protected static LFSR z_register;
	
	public A5_1Sys(String pass) {
		String bin_pass = ConversionUtil.textToBinStr(pass);
		x_register = LFSR.constructFromBinStr(bin_pass.substring(0,19), new int[] {13,16,17,18});
		y_register = LFSR.constructFromBinStr(bin_pass.substring(19,41), new int[] {20,21});
		z_register = LFSR.constructFromBinStr(bin_pass.substring(41,64), new int[] {7,20,21,22});
	}
	
	public String encrypt(String plaintext) {
		Bit[] bit_plaintext_array = ConversionUtil.textToBitArr(plaintext);
		for (int i = 0; i < bit_plaintext_array.length; i++) {
			bit_plaintext_array[i].xorM(generateKey());
		}
		
		String ciphertext=ConversionUtil.bitArrToHexStr(bit_plaintext_array);
		
		return ciphertext;
	}
	
	public String decrypt(String ciphertext) {
		ciphertext = ciphertext.toLowerCase();
		String ciphertext_binString = ConversionUtil.hexStrToBinStr(ciphertext);
		Bit[] bit_ciphertext_array = ConversionUtil.binStrToBitArr(ciphertext_binString);
		for (int i = 0; i < bit_ciphertext_array.length; i++) {
			bit_ciphertext_array[i].xorM(generateKey());
		}
		
		String plaintext_hexString = ConversionUtil.bitArrToHexStr(bit_ciphertext_array);
		String plaintext = ConversionUtil.hexStrToText(plaintext_hexString);
		return plaintext;
	}

	public Bit generateKey() {
		Bit gx = Bit.constructFromInteger(0);
		Bit gy = Bit.constructFromInteger(0);
		Bit gz = Bit.constructFromInteger(0);
		
		Bit x8 = x_register.bitAt(8);
		Bit y10 = y_register.bitAt(10);
		Bit z10 = z_register.bitAt(10);
		
		Bit m = Function.maj(new Bit[] {x8,y10,z10});
		if(m.equal(x8)) {
			gx = x_register.stepM();
		}
		if(m.equal(y10)) {
			gy = y_register.stepM();
		}
		if(m.equal(z10)) {
			gz = z_register.stepM();
		}
		
		Bit k = gx.xor(gy).xor(gz);
		return k;
	}
	
}































