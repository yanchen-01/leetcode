package edu.sjsu.crypto.ciphersys.stream;

import edu.sjsu.yazdankhah.crypto.util.abstracts.PkzipAbs;
import edu.sjsu.yazdankhah.crypto.util.cipherutils.ConversionUtil;
import edu.sjsu.yazdankhah.crypto.util.primitivedatatypes.UByte;
import edu.sjsu.yazdankhah.crypto.util.primitivedatatypes.Word;
import edu.sjsu.yazdankhah.crypto.util.cipherutils.StringUtil;

/**
 * PKZIP cipher system
 * @author sida
   Customer: Yan Chen
 */
public class PkzipSys extends PkzipAbs {
	protected static Word word_x;
	protected static Word word_y;
	protected static Word word_z;
	
	public PkzipSys(String pass) {
		String bin_pass = ConversionUtil.textToBinStr(pass);
		bin_pass = StringUtil.rightTruncRightPadWithZeros(bin_pass, 96);
		
		word_x = Word.constructFromBinStr(bin_pass.substring(0,32));
		word_y = Word.constructFromBinStr(bin_pass.substring(32,64));
		word_z = Word.constructFromBinStr(bin_pass.substring(64,96));
	}
	
	public String encrypt(String plaintext) {
		UByte[] ubit_plaintext_array = ConversionUtil.textToUByteArr(plaintext);
		UByte[] ubit_ciphertext_array =  new UByte[ubit_plaintext_array.length];
		for (int i = 0; i < ubit_plaintext_array.length; i++) {
			ubit_ciphertext_array[i]=ubit_plaintext_array[i].xor(generateKey(word_z));
			update(word_x,word_y,word_z,ubit_plaintext_array[i]);
		}
		String ciphertext=ConversionUtil.ubyteArrToHexStr(ubit_ciphertext_array);
		
		return ciphertext;
	}
	
	public String decrypt(String ciphertext) {
		String ciphertext_binString = ConversionUtil.hexStrToBinStr(ciphertext);
		UByte[] ubit_ciphertext_array = ConversionUtil.binStrToUByteArr(ciphertext_binString);
		
		for (int i = 0; i < ubit_ciphertext_array.length; i++) {
			ubit_ciphertext_array[i].xorM(generateKey(word_z));
			update(word_x,word_y,word_z,ubit_ciphertext_array[i]);
		}
		
		String plaintext=ConversionUtil.ubyteArrToText(ubit_ciphertext_array);
		return plaintext;
	}
	
	public UByte generateKey(Word word_Z) {
		//⟨Z ∨ 3⟩16..31
		Word word_T = word_Z.or(THREE_WORD).rightHalfAsWord();
		
		//⟨(t * (t ⊕ 1))≫8 ⟩24..31
		UByte ubyte_k = word_T.multiplyMod2p32(word_T.xor(Word.ONE_WORD)).shiftRight(8).byteAt(3);     
		
		return ubyte_k;
	}
	
	public void update(Word word_X, Word word_Y, Word word_Z, UByte ubyte_p) {
		//CRC(X, p)
		CRC(word_X,ubyte_p);
		
		//(Y + ⟨X⟩24...31) *134775813 + 1 (mod 232)
		word_Y = word_Y.addMod2p32M(Word.constructFromUByte((word_X.byteAt(3)))).multiplyMod2p32M(UPDATE_CONST_WORD).addMod2p32M(Word.ONE_WORD);            
		
		//CRC(Z, ⟨Y⟩0...7)
		CRC(word_Z,word_Y.byteAt(0));
	}
	
	//Cyclic Redundancy Check
	public void CRC(Word word_V, UByte ubyte_p) {
		//V = V ⊕ b
		word_V.xorM(Word.constructFromUByte(ubyte_p));
		for(int i=0; i<CRC_ITERATION; i++) {
			//V = V ≫ 1
			word_V.shiftRightM(1);
			//if V is odd
			if(word_V.toLong()%2 != 0) {
				//V = V ⊕ 0xedb88320
				word_V.xorM(CRC_CONST_WORD);
			}
		}
	}
}































