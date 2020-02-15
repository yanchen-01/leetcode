package edu.sjsu.crypto.ciphersys.classic;

import java.util.*;

import edu.sjsu.yazdankhah.crypto.util.abstracts.DoubleTransAbs;
import edu.sjsu.yazdankhah.crypto.util.cipherutils.ConversionUtil;
import edu.sjsu.yazdankhah.crypto.util.matrixdatatypes.CharMatrix;

/**
 * Double Transposition cipher
 * @author sida
   Customer: Yan Chen
 */
public class DoubleTransSys extends DoubleTransAbs {
	protected static Map<String, int[]> key;
	
	public DoubleTransSys(int[] rowsPerm, int[] colsPerm) {
		//generate key
		key = new HashMap<String, int[]>();
		key.put("rowsPerm", rowsPerm);
		key.put("colsPerm", colsPerm);
	}
	
	//encrypt
	public String encrypt(String plaintext) {
		plaintext = plaintext.toLowerCase();
		CharMatrix[] matrix_plaintext = ConversionUtil.textToCharMatrixArr(key.get("rowsPerm").length, key.get("colsPerm").length, plaintext);
		
		for (int i = 0; i < matrix_plaintext.length; i++) {
			matrix_plaintext[i]=matrix_plaintext[i].permuteRows(key.get("rowsPerm"));
			matrix_plaintext[i]=matrix_plaintext[i].permuteCols(key.get("colsPerm"));
		}
		
		String ciphertext = ConversionUtil.charMatrixArrToText(matrix_plaintext);
		return ciphertext;
	}
	
	//decrypt
	public String decrypt(String ciphertext) {
		ciphertext = ciphertext.toLowerCase();
		CharMatrix[] matrix_ciphertext = ConversionUtil.textToCharMatrixArr(key.get("rowsPerm").length, key.get("colsPerm").length, ciphertext);
		
		for (int i = 0; i < matrix_ciphertext.length; i++) {
			matrix_ciphertext[i]=matrix_ciphertext[i].inversePermuteRows(key.get("rowsPerm"));
			matrix_ciphertext[i]=matrix_ciphertext[i].inversePermuteCols(key.get("colsPerm"));
		}
		
		String plaintext = ConversionUtil.charMatrixArrToText(matrix_ciphertext);
		return plaintext.trim();
	}

	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	

}
