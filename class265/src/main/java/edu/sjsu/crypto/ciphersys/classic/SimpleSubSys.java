package edu.sjsu.crypto.ciphersys.classic;

import java.util.*;
import edu.sjsu.yazdankhah.crypto.util.cipherutils.*;
import edu.sjsu.yazdankhah.crypto.util.shiftregisters.CSR;

public class SimpleSubSys extends SimpleSubAbs {

	public static void main(String[] args) {
		//encrypt
		/*
		int key = 5;
		SimpleSubSys sys = new SimpleSubSys(key);
		String plaintext = "defend , the . ? east wall of the castle";
		System.out.println("Ciphertext = ['" + sys.encrypt(plaintext)+ "']");
		String ciphertext = "IJKJSI YMJ JFXY BFQQ TK YMJ HFXYQJ";
		System.out.println("Recovered Plaintext = [" + sys.decrypt(ciphertext)+ "]");
		*/
		
		int key = 15;
		SimpleSubSys sys = new SimpleSubSys(key);
		String plaintext = "my name is sida, I like this class!";
		System.out.println("Ciphertext = ['" + sys.encrypt(plaintext)+ "']");
		String ciphertext = "CD RAPHH RPCRTAAPIXDC";
		System.out.println("Recovered Plaintext = [" + sys.decrypt(ciphertext)+ "]");
	}
	
	public SimpleSubSys(int key) {
		key = key % 26;
		char[] alpha = ("abcdefghijklmnopqrstuvwxyz" + "abcdefghijklmnopqrstuvwxyz").toCharArray();
		
		encryptionTable = new HashMap<Character,Character>();
		decryptionTable = new HashMap<Character,Character>();
		
		for (int i = 0; i < alpha.length; i++) {
			encryptionTable.put(alpha[i], alpha[i+key]);
			decryptionTable.put(alpha[i+key], alpha[i]);
			if(i == 25) {
				break;
			}
		}
		
		System.out.println(encryptionTable);
		System.out.println(decryptionTable);
	}

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

































