package edu.sjsu.crypto.ciphersys.classic;

import static org.junit.jupiter.api.Assertions.*;

import org.junit.jupiter.api.Test;

class DoubleTransSysTest {

	@Test
	void DoubleTransSys() {
		int[] rowsPerm = { 2, 0, 1 };
		int[] colsPerm = { 3, 2, 0, 1 };
		DoubleTransSys sys = new DoubleTransSys(rowsPerm, colsPerm);
		
		String plaintext = "my name is sida, i love this class";
		System.out.println("ciphertext = [" + sys.encrypt(plaintext)+ "]\n");
		
		String ciphertext = " ssi nyme mae voa,di li   ssishtlac ";
		System.out.println("Recovered Plaintext = [" + sys.decrypt(ciphertext)+ "]\n");
		
		/*
		int[] rowsPerm = { 3, 1, 2, 0 };
		int[] colsPerm = { 2, 1, 0 };
		DoubleTransSys sys = new DoubleTransSys(rowsPerm, colsPerm);
		
		String plaintext = "my name is sida, i love this class";
		System.out.println("ciphertext = [" + sys.encrypt(plaintext)+ "]\n");
		
		String ciphertext = "THGTS IARTEG     S   'A";
		System.out.println("Recovered Plaintext = [" + sys.decrypt(ciphertext)+ "]\n");
		*/
	}
}
