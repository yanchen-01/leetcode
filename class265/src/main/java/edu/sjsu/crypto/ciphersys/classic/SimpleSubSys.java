package edu.sjsu.crypto.ciphersys.classic;

import java.util.*;

import edu.sjsu.yazdankhah.crypto.util.abstracts.SimpleSubAbs;
import edu.sjsu.yazdankhah.crypto.util.shiftregisters.CSR;


/**
 * Parameterized Caesar Cipher
 * @author sida
   Customer: Yan Chen
 */
public class SimpleSubSys extends SimpleSubAbs {
	protected static Map<Character, Character> encryptionTable;
	protected static Map<Character, Character> decryptionTable;
	
	public SimpleSubSys(int key) {
		encryptionTable = new HashMap<Character,Character>();
		decryptionTable = new HashMap<Character,Character>();
		
		String alpha = "abcdefghijklmnopqrstuvwxyz";
		CSR alpha_csr = CSR.constructFromText(alpha);
		
		CSR encryption = alpha_csr.rotateLeft(key);
		encryptionTable = makeLookupTable(alpha_csr,encryption);
		
		CSR decryption = alpha_csr.rotateRight(key);
		decryptionTable = makeLookupTable(alpha_csr,decryption);
	}

	//using the encryptionTable to encode the plantext
	public String encrypt(String plaintext) {
		plaintext = plaintext.toLowerCase();
		char[] tmp_plaintext = plaintext.toCharArray();
		String rs = "";
		
		for (int i = 0; i < tmp_plaintext.length; i++) {
			if(Character.isLetter(tmp_plaintext[i])) {
				rs += encryptionTable.get(tmp_plaintext[i]);
			}else {
				rs += tmp_plaintext[i];
			}
		}
		
		return rs.toUpperCase();
	}

	//using the decryptionTable to decode the plantext
	public String decrypt(String ciphertext) {
		ciphertext = ciphertext.toLowerCase();
		char[] tmp_ciphertext = ciphertext.toCharArray();
		String rs = "";
		
		for (int i = 0; i < tmp_ciphertext.length; i++) {
			if(Character.isLetter(tmp_ciphertext[i])) {
				rs += decryptionTable.get(tmp_ciphertext[i]);
			}else {
				rs += tmp_ciphertext[i];
			}
		}
		
		return rs;
	}
}

































