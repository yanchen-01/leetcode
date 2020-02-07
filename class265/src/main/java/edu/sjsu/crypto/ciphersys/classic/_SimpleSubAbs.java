package edu.sjsu.crypto.ciphersys.classic;

import java.util.*;
import edu.sjsu.yazdankhah.crypto.util.shiftregisters.CSR;

/**
 * Parameterized Caesar Cipher
 * @author sida
 *
 	This is a warm up assignment to implement one of the simplest cryptographic algorithms to
	enter cryptography world, and to practice CryptoUtil.
 
 	Your program is required to be reasonably documented specially the public members.
	There should not be any magic numbers in your program!
	Pick a customer
		You are required to pick a classmate as your customer.
		Pick a key and encrypt a message by your program.
		Send your key and the ciphertext to your customer.
		Your customer is required to decrypt your ciphertext by her/his program for double checking both programs.
		Mention your customer's name in your class document.
		
		
	Customer: Yan Chen
 */
public abstract class _SimpleSubAbs {
	//Two hash-maps serve as the lookup tables for encryption and decryption
	protected static Map<Character, Character> encryptionTable;
	protected static Map<Character, Character> decryptionTable;
	
	//Two methods, encrypt and decrypt, are enforced when your class extends SimpleSubAbs abstract class.
	protected abstract String encrypt(String plaintext);
	protected abstract String decrypt(String ciphertext);
	
	//This method combines two CSR data structures and return a hash-map that can be used as a lookup table.
	//protected static Map<Character, Character> makeLookupTable(CSR csr1, CSR csr2) {
		//return null;
	//}
}
































